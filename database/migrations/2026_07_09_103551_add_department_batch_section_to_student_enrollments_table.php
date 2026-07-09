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
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->foreignId('department_id')->after('academic_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->after('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->after('batch_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('batch_id');
            $table->dropConstrainedForeignId('section_id');
        });
    }
};
