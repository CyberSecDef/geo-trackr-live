<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Minimal, privacy-preserving record used only for rate limiting / abuse detection.
        // No player coordinates are stored (see spec §10 PRIV-1).
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasure_id')->constrained()->cascadeOnDelete();
            // SHA-256 of (ip + app secret salt) — never the raw IP (PRIV-4).
            $table->char('ip_hash', 64)->nullable();
            $table->timestamp('attempted_at');

            $table->index(['treasure_id', 'attempted_at']);
            $table->index(['ip_hash', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
