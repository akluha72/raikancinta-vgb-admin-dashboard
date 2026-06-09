<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Owner login for the dashboard (single admin for MVP).
        User::firstOrCreate(
            ['email' => 'owner@raikancinta.com'],
            [
                'name' => 'Owner',
                'password' => 'password',
            ]
        );

        $this->call(EventSeeder::class);
    }
}
