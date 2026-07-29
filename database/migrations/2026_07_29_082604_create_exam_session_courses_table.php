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
        Schema::create('exam_session_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('batch_id')
                ->constrained();

            $table->foreignId('course_id')
                ->constrained();

            $table->unsignedInteger('total_students')->default(0);

            $table->timestamps();
            $table->unique([
                'exam_session_id',
                'batch_id',
                'course_id',
            ], 'exam_session_course_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_session_courses');
    }
};
