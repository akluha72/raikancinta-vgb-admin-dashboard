<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Models\Event;
use App\Models\GuestbookEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GuestSubmissionController extends Controller
{
    /**
     * Store a guest submission for the event identified by its slug.
     *
     * The {event} arg is resolved from the slug by route-model binding, so the
     * event_id is always derived server-side — the client cannot supply or
     * override it. A missing slug 404s before we ever get here.
     */
    public function store(StoreSubmissionRequest $request, Event $event): JsonResponse
    {
        $disk = Config::get('guestbook.disk', 'public');

        // Track what we write so we can clean up if the DB insert fails.
        $storedPaths = [];

        try {
            // Audio is required (validated already); photo is optional.
            $audioPath = $this->storeFile($request->file('audio'), $event->id, 'audio', $disk);
            $storedPaths[] = $audioPath;

            $photoPath = null;
            if ($request->hasFile('photo')) {
                $photoPath = $this->storeFile($request->file('photo'), $event->id, 'photos', $disk);
                $storedPaths[] = $photoPath;
            }

            // Wrap the insert so a failure here lets us roll back both the row
            // and the just-written files (no orphaned uploads, no silent loss).
            $entry = DB::transaction(function () use ($event, $request, $audioPath, $photoPath) {
                return GuestbookEntry::create([
                    'event_id' => $event->id,
                    'guest_name' => $request->validated('guest_name'),
                    'event_date' => $event->wedding_date?->toDateString() ?? now()->toDateString(),
                    'audio' => $audioPath,
                    'photo' => $photoPath,
                    'guest_message' => $request->validated('guest_message'),
                    'status' => 'pending',
                ]);
            });
        } catch (\Throwable $e) {
            // Roll back any files we managed to write before the failure.
            foreach ($storedPaths as $path) {
                Storage::disk($disk)->delete($path);
            }

            Log::error('Guest submission failed', [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not save your submission. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Submission received',
            'data' => ['id' => $entry->id],
        ], 201);
    }

    /**
     * Store an upload under an event-namespaced path and return the relative
     * path stored in the DB: events/{event_id}/{kind}/{uuid}.{ext}
     */
    private function storeFile(UploadedFile $file, int $eventId, string $kind, string $disk): string
    {
        // Trust the detected extension over the client-supplied filename.
        $ext = $file->extension() ?: $file->getClientOriginalExtension();
        $name = Str::uuid().'.'.$ext;

        return $file->storeAs("events/{$eventId}/{$kind}", $name, ['disk' => $disk]);
    }
}
