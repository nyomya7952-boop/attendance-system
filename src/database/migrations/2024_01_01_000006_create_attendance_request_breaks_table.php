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
        Schema::create('attendance_request_breaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_request_id');
            $table->dateTime('break_start_at');
            $table->dateTime('break_end_at');
            $table->timestamps();

            $table->foreign('attendance_request_id')->references('id')->on('attendance_requests')->onDelete('cascade');
            $table->index('attendance_request_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_request_breaks');
    }
};

