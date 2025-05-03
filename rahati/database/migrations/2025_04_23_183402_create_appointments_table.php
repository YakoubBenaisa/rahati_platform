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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users');
            $table->foreignId('center_id')->constrained('centers');
            $table->foreignId('provider_id')->nullable()->constrained('users');
            $table->dateTime('appointment_datetime');
            $table->integer('appointment_duration')->comment('Duration in minutes');
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled, no-show
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
