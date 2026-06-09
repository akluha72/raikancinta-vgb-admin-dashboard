<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use App\Models\GuestbookEntry;
use App\Services\EventCredentialGenerator;
use App\Services\EventQrService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

        return redirect()
            ->route('events.show', $event)
            ->with('status', "Event \"{$event->couple_name}\" created.");
    }

    /**
     * Show one event: shareable links, submission counts, and event info.
     */
    public function show(Event $event): View
    {
        // Counts grouped by status in a single query (no per-status round-trips).
        $byStatus = GuestbookEntry::query()
            ->where('event_id', $event->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $counts = [
            'total' => (int) $byStatus->sum(),
            'pending' => (int) $byStatus->get('pending', 0),
            'approved' => (int) $byStatus->get('approved', 0),
            'binned' => (int) $byStatus->get('binned', 0),
        ];

        return view('events.show', [
            'event' => $event,
            'counts' => $counts,
            'guestUrl' => $this->qr->guestUrl($event),
            'galleryUrl' => rtrim(config('guestbook.gallery_base_url'), '/').'/'.$event->slug,
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
