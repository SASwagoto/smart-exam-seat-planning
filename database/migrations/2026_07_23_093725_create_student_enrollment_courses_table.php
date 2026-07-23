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
        Schema::create('student_enrollment_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_course_enrollment_id')
                ->constrained('student_course_enrollments', 'id', 'stu_crs_enr_fk')
                ->cascadeOnDelete();
                
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('enrollment_type')->default('Regular');
            $table->string('status')->default('Enrolled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_enrollment_courses');
    }
};
