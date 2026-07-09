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
        Schema::create('teacher_course_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('academic_session_id')->constrained()->cascadeOnDelete();

            $table->foreignId('department_id')->constrained()->cascadeOnDelete();

            $table->foreignId('batch_id')->constrained()->cascadeOnDelete();

            $table->foreignId('section_id')->constrained()->cascadeOnDelete();

            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();

            $table->foreignId('course_id')->constrained()->cascadeOnDelete();

            $table->timestamps();

            $table->index('department_id', 'idx_department');

            $table->index('batch_id', 'idx_batch');

            $table->unique(
                [
                    'academic_session_id',
                    'course_id',
                    'batch_id',
                    'section_id',
                ],
                'tca_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_course_assignments');
    }
};
