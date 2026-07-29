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
        Schema::create('exam_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_session_course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();

            $table->string('row_label', 20);
            $table->string('column_label', 20);
            $table->string('seat_label', 30);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_seats');
    }
};
