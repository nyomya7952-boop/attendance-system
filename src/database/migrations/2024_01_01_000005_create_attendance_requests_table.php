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
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_id')->nullable();
            $table->unsignedBigInteger('parent_request_id')->nullable();
            $table->dateTime('requested_started_at');
            $table->dateTime('requested_ended_at');
            $table->string('reason', 255);
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->string('status', 20)->nullable();
            $table->timestamps();

            $table->foreign('attendance_id')->references('id')->on('attendances');
            $table->foreign('parent_request_id')->references('id')->on('attendance_requests');
            $table->foreign('requested_by')->references('id')->on('users');
            $table->foreign('approver_id')->references('id')->on('users');
            $table->index('attendance_id');
            $table->index('parent_request_id');
            $table->index('requested_by');
            $table->index('approver_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_requests');
    }
};

