<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the couple photo + greeting audio media columns. These store the
     * relative disk path (same convention as guestbook_entries.photo/audio);
     * the public *_url variants are resolved at read time, never stored.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('couple_photo')->nullable()->after('plan_tier');
            $table->string('greeting_audio')->nullable()->after('couple_photo');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['couple_photo', 'greeting_audio']);
        });
    }
};
