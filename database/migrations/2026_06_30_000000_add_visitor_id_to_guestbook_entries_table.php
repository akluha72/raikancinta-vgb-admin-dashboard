<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stable per-device id sent by the guest app so the feed can report a
     * distinct-participant count ("Joined"). Nullable: storage may be blocked
     * on a guest's device, and a null id counts as one anonymous participant.
     * Indexed because the feed runs a DISTINCT count over it per event.
     */
    public function up(): void
    {
        Schema::table('guestbook_entries', function (Blueprint $table) {
            $table->string('visitor_id', 64)->nullable()->index()->after('guest_message');
        });
    }

    public function down(): void
    {
        Schema::table('guestbook_entries', function (Blueprint $table) {
            $table->dropIndex(['visitor_id']);
            $table->dropColumn('visitor_id');
        });
    }
};
