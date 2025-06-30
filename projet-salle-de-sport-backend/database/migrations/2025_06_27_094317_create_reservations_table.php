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
        Schema::create('reservations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('member_id')->constrained();
        $table->foreignId('service_id')->constrained();
        $table->date('date');
        $table->time('start_time');
        $table->time('end_time');
        $table->enum('status', ['confirmed', 'cancelled', 'completed'])->default('confirmed');
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
