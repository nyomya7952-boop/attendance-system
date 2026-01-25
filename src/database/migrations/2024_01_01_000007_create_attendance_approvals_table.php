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
        Schema::create('attendance_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->unsignedBigInteger('attendance_request_id');
            $table->unsignedBigInteger('approved_by');
            $table->timestamp('approved_at')->nullable();
            $table->string('status', 20)->nullable();
            $table->dateTime('final_started_at')->nullable();
            $table->dateTime('final_ended_at')->nullable();
            $table->integer('final_break_minutes')->nullable();
            $table->integer('final_work_minutes')->nullable();
            $table->timestamps();

            $table->foreign('attendance_id')->references('id')->on('attendances');
            $table->foreign('attendance_request_id')->references('id')->on('attendance_requests');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->index('attendance_request_id');
            $table->index('approved_by');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_approvals');
    }
};

