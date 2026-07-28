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
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_slot_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('total_students')->default(0);
            $table->integer('total_allocated_seats')->default(0);
            $table->string('status')->default('draft'); // draft, published, completed
            $table->timestamps();

            // একই দিনে এবং একই স্লটে ডুপ্লিকেট এক্সাম শিডিউল এড়াতে
            $table->unique(['exam_id', 'exam_slot_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedules');
    }
};
