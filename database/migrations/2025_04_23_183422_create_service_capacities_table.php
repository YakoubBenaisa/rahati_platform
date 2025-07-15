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
        Schema::create('service_capacities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('center_id')->constrained('centers');
            $table->string('service_type'); // appointment, accommodation, etc.
            $table->integer('max_capacity');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Ensure unique capacity rules per center, service type, date, and time range
            $table->unique(['center_id', 'service_type', 'date', 'start_time', 'end_time'], 'unique_capacity_rule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_capacities');
    }
};
