<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Base `guestbook_entries` table. Same story as create_events_table: it
     * only existed in the MySQL schema dump, so fresh SQLite setups were
     * missing it. Guarded by hasTable() to stay a NO-OP where it already
     * exists. Columns mirror the schema dump (status enum is a plain string
     * here for SQLite/MySQL portability); the add_visitor_id migration adds the
     * visitor_id column afterwards.
     */
    public function up(): void
    {
        if (Schema::hasTable('guestbook_entries')) {
            return;
        }

        Schema::create('guestbook_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('guest_name', 100);
            $table->date('event_date');
            $table->string('photo')->nullable();
            $table->string('audio');
            $table->text('guest_message')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guestbook_entries');
    }
};
