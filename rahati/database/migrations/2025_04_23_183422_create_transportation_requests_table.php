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
        Schema::create('transportation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('appointment_id')->nullable()->constrained('appointments');
            $table->string('pickup_location');
            $table->string('dropoff_location');
            $table->dateTime('pickup_time');
            $table->string('transportation_type')->default('standard'); // standard, wheelchair-accessible, etc.
            $table->integer('number_of_passengers')->default(1);
            $table->string('status')->default('pending'); // pending, confirmed, completed, cancelled
            $table->text('special_instructions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transportation_requests');
    }
};
