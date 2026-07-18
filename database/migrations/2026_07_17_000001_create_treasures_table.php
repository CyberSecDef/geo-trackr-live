<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // 8-char unambiguous uppercase code (see config/geocache.php alphabet).
            $table->char('code', 8)->unique();
            $table->string('message', 1000);
            // Secret location. Never sent to clients. High precision for ~1cm resolution.
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 11, 7);
            // GPS accuracy (meters) reported by the creator's browser at capture time.
            $table->float('created_accuracy_m')->nullable();
            $table->enum('status', ['active', 'paused'])->default('active');
            $table->unsignedInteger('unlock_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treasures');
    }
};
