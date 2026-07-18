<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasure_id')->constrained()->cascadeOnDelete();
            // Null user_id = anonymous player (login not required to test/unlock).
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('unlocked_at');

            $table->index(['treasure_id', 'unlocked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unlocks');
    }
};
