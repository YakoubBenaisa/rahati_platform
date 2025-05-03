<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers');
            $table->string('room_number');
            $table->string('type'); // single, double, suite, etc.
            $table->text('description')->nullable();
            $table->decimal('price_per_night', 10, 2);
            $table->integer('capacity');
            $table->boolean('is_accessible')->default(false);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            // Ensure room numbers are unique within a center
            $table->unique(['center_id', 'room_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
