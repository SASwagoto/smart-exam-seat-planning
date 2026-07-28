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
        Schema::create('exam_schedule_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->integer('effective_capacity')->default(0);
            $table->timestamps();

            // একটি শিডিউলে একই রুম দুবার যোগ করা আটকাবে
            $table->unique(['exam_schedule_id', 'room_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedule_rooms');
    }
};
