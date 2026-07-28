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
        Schema::create('exam_schedule_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_schedule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_course_assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_course_assignment_item_id')->constrained();
            $table->foreignId('course_id')->constrained();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete(); // 👈 যোগ করা হলো (ব্যাচ ট্র্যাকিং সহজ করতে)
            $table->integer('student_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedule_courses');
    }
};
