<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Leads captured from the website (POST /api/leads) or keyed in manually.
     * A lead is a person/contact; their deals live in the `projects` table.
     *
     * `source` / `status` are plain strings (not MySQL enum) so the same
     * migration runs on SQLite (dev) and MySQL (prod). Allowed values are
     * defined in config/crm.php.
     */
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->index();
            $table->string('email')->nullable();
            $table->string('source')->default('website');
            $table->string('status')->default('new');
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
