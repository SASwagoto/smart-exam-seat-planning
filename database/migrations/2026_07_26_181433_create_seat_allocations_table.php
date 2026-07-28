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
        Schema::create('seat_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_course_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained();
            $table->integer('row_no');
            $table->integer('col_no');
            $table->string('seat_number'); // e.g. "R1-C2"

            $table->timestamps();

            // ১. একই শিডিউল ও রুমে নির্দিষ্ট রো ও কলামে ডুপ্লিকেট সিট পড়বে না
            $table->unique(['exam_schedule_id', 'room_id', 'row_no', 'col_no'], 'unique_seat_per_slot');

            // 👈 ২. যোগ করা হলো: একই শিডিউলে (স্লটে) একজন স্টুডেন্ট দুটি সিটে বসতে পারবে না
            $table->unique(['exam_schedule_id', 'student_id'], 'unique_student_per_slot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_allocations');
    }
};
