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
            
            $table->integer('row_no');
            $table->integer('col_no');
            $table->string('seat_number'); // e.g. "R1-C2"
            
            $table->timestamps();

            $table->unique(['exam_schedule_id', 'room_id', 'row_no', 'col_no'], 'unique_seat_per_slot');
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
