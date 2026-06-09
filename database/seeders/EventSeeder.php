<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\GuestbookEntry;
use App\Services\EventCredentialGenerator;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    /**
     * Seed one populated test event so the dashboard UI is not empty.
     */
    public function run(): void
    {
        $gen = app(EventCredentialGenerator::class);

        $coupleName = 'Sarah & Ali';
        $weddingDate = now()->addDays(30)->toDateString();

        $event = Event::firstOrCreate(
            ['couple_name' => $coupleName],
            [
                'slug' => $gen->uniqueSlug($coupleName),
                'wedding_date' => $weddingDate,
                'plan_tier' => 'premium',
                'gallery_pin' => $gen->galleryPin(),
            ]
        );

        // A spread of submissions across statuses so the stat cards show data.
        $samples = [
            ['Auntie Mei', 'approved'],
            ['Daniel Tan', 'approved'],
            ['Priya', 'approved'],
            ['Uncle Raj', 'pending'],
            ['Wei Ling', 'pending'],
            ['Anonymous Guest', 'binned'],
        ];

        if ($event->entries()->count() === 0) {
            foreach ($samples as [$guest, $status]) {
                GuestbookEntry::create([
                    'event_id' => $event->id,
                    'guest_name' => $guest,
                    'event_date' => $weddingDate,
                    'photo' => 'samples/photo.jpg',
                    'audio' => 'samples/voice.webm',
                    'guest_message' => "Congratulations {$coupleName}! Wishing you a lifetime of happiness.",
                    'status' => $status,
                ]);
            }
        }
    }
}
