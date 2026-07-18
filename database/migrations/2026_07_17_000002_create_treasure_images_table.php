<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treasure_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treasure_id')->constrained()->cascadeOnDelete();
            $table->string('mime_type', 100);
            $table->unsignedInteger('byte_size');
            // Image stored as a BLOB in MySQL (hosting decision). MEDIUMBLOB = up to 16MB;
            // the app caps uploads well below that (see config/geocache.php image_max_bytes).
            // ->change() is not needed; binary() maps to BLOB, and we upgrade the column type
            // to MEDIUMBLOB via a raw statement below for headroom.
            $table->binary('data');
            $table->timestamps();

            $table->unique('treasure_id'); // one image per treasure in v1
        });

        // Ensure the BLOB column is MEDIUMBLOB (Laravel's binary() defaults to BLOB/64KB on MySQL).
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE treasure_images MODIFY data MEDIUMBLOB NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('treasure_images');
    }
};
