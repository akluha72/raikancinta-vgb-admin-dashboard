<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Base `events` table.
     *
     * Historically this table was created out-of-band and only lived in the
     * MySQL schema dump (database/schema/mysql-schema.sql), so a fresh SQLite
     * setup (dev + the test suite) had no `events` table and every migration
     * that ALTERs it failed. This migration backfills the create step.
     *
     * It is guarded by hasTable() so it is a NO-OP on any environment where the
     * table already exists (e.g. production created from the schema dump) and
     * only creates the table where it is genuinely missing. Columns mirror the
     * schema dump's base definition — the later add_media/venue/visitor_id
     * migrations layer their columns on top.
     */
    public function up(): void
    {
        if (Schema::hasTable('events')) {
            return;
        }

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('couple_name', 150);
            $table->date('wedding_date')->nullable();
            $table->string('gallery_pin', 10)->nullable();
            $table->string('plan_tier', 50)->default('basic');
            // Only created_at — the Event model disables updated_at.
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
