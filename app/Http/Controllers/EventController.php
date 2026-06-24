<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use App\Models\GuestbookEntry;
use App\Services\EventCredentialGenerator;
use App\Services\EventQrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        private readonly EventCredentialGenerator $credentials,
        private readonly EventQrService $qr,
    ) {}

    /**
     * List all events, newest first, with a total submission count and an
     * optional couple-name search.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $events = Event::query()
            ->withCount('entries')
            ->when($search !== '', fn ($query) => $query->where('couple_name', 'like', "%{$search}%"))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('events.index', [
            'events' => $events,
            'search' => $search,
        ]);
    }

    /**
     * Show the create-event form.
     */
    public function create(): View
    {
        return view('events.create');
    }

    /**
     * Persist a new event, generating its unique slug and gallery PIN.
     */
    public function store(StoreEventRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $event = Event::create([
            'couple_name' => $data['couple_name'],
            'wedding_date' => $data['wedding_date'] ?? null,
            'plan_tier' => $data['plan_tier'],
            'slug' => $this->credentials->uniqueSlug($data['couple_name']),
            'gallery_pin' => $this->credentials->galleryPin(),
        ]);

        // Files are namespaced by the event id, so they're stored after the
        // row exists. Persist the relative paths back onto the event.
        $media = [];
        if ($request->hasFile('couple_photo')) {
            $media['couple_photo'] = $this->storeMedia($request->file('couple_photo'), $event->id, 'photos');
        }
        if ($request->hasFile('greeting_audio')) {
            $media['greeting_audio'] = $this->storeMedia($request->file('greeting_audio'), $event->id, 'audio');
        }
        if ($media !== []) {
            $event->update($media);
        }

        return redirect()
            ->route('events.show', $event)
            ->with('status', "Event \"{$event->couple_name}\" created.");
    }

    /**
     * Show the edit-event form.
     */
    public function edit(Event $event): View
    {
        return view('events.edit', ['event' => $event]);
    }

    /**
     * Update an event's details and, optionally, its couple photo / greeting
     * audio. Uploading a new file replaces and deletes the old one; omitting a
     * file leaves the existing media untouched.
     */
    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();

        $attributes = [
            'couple_name' => $data['couple_name'],
            'wedding_date' => $data['wedding_date'] ?? null,
            'plan_tier' => $data['plan_tier'],
        ];

        $disk = Config::get('guestbook.disk', 'public');

        if ($request->hasFile('couple_photo')) {
            $attributes['couple_photo'] = $this->storeMedia($request->file('couple_photo'), $event->id, 'photos');
        }
        if ($request->hasFile('greeting_audio')) {
            $attributes['greeting_audio'] = $this->storeMedia($request->file('greeting_audio'), $event->id, 'audio');
        }

        // Capture the paths being replaced so we can delete them after the
        // update succeeds (avoids orphaned files on the disk).
        $oldPhoto = $event->couple_photo;
        $oldAudio = $event->greeting_audio;

        $event->update($attributes);

        if (isset($attributes['couple_photo']) && $oldPhoto && $oldPhoto !== $attributes['couple_photo']) {
            Storage::disk($disk)->delete($oldPhoto);
        }
        if (isset($attributes['greeting_audio']) && $oldAudio && $oldAudio !== $attributes['greeting_audio']) {
            Storage::disk($disk)->delete($oldAudio);
        }

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Event updated.');
    }

    /**
     * Store an event-level media upload under an event-namespaced path and
     * return the relative path stored in the DB:
     * events/{event_id}/event-{kind}/{uuid}.{ext}
     *
     * Kept separate from guest submissions so editing event media never
     * touches guest uploads.
     */
    private function storeMedia(UploadedFile $file, int $eventId, string $kind): string
    {
        $disk = Config::get('guestbook.disk', 'public');

        // Trust the detected extension over the client-supplied filename.
        $ext = $file->extension() ?: $file->getClientOriginalExtension();
        $name = Str::uuid().'.'.$ext;

        return $file->storeAs("events/{$eventId}/event-{$kind}", $name, ['disk' => $disk]);
    }

    /**
     * Show one event: shareable links, submission counts, and event info.
     */
    public function show(Event $event): View
    {
        // Moderation is disabled for now, so only the total submission count is
        // shown. The per-status breakdown can return when approval is re-enabled.
        $total = (int) GuestbookEntry::query()
            ->where('event_id', $event->id)
            ->count();

        return view('events.show', [
            'event' => $event,
            'total' => $total,
            'guestUrl' => $this->qr->guestUrl($event),
            'galleryUrl' => rtrim(config('guestbook.gallery_base_url'), '/').'/?event='.urlencode($event->slug),
            // Inline SVG QR for the guest submission URL (rendered with {!! !!}).
            'qrSvg' => $this->qr->render($event, 'svg', 240),
        ]);
    }

    /**
     * Regenerate the event's 6-digit gallery PIN.
     */
    public function resetPin(Event $event): RedirectResponse
    {
        $event->update([
            'gallery_pin' => $this->credentials->galleryPin(),
        ]);

        return redirect()
            ->route('events.show', $event)
            ->with('status', 'Gallery PIN regenerated.');
    }

    /**
     * Download the event's guest-URL QR for printing (signage, table cards).
     * Defaults to SVG (crisp at any print size); also supports PNG raster.
     */
    public function qr(Event $event, Request $request): Response
    {
        $format = $request->query('format') === 'png' ? 'png' : 'svg';

        $qr = $this->qr->render($event, $format);

        $mime = $format === 'png' ? 'image/png' : 'image/svg+xml';
        $filename = $event->slug.'-qr.'.$format;

        return response($qr, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
