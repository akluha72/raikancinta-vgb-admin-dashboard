<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Projects are the deals/opportunities in the sales pipeline. A lead can
     * have many projects; each may optionally link to a wedding `event` once
     * the guestbook is provisioned (nullOnDelete so deleting an event just
     * unlinks it). `value` is the contract amount, `cost` the expenses;
     * profit is derived (value - cost).
     *
     * `stage` is a plain string (values in config/crm.php 'pipeline') for
     * SQLite/MySQL portability. `closed_at` is stamped when the project enters
     * a terminal stage (won/lost).
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('stage')->default('new')->index();
            $table->decimal('value', 12, 2)->default(0);
            $table->decimal('cost', 12, 2)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
