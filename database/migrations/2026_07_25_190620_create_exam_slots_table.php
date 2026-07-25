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
        Schema::create('exam_slots', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., Slot 1 (Morning), Slot 2
            $table->time('start_time'); // e.g., 09:00:00
            $table->time('end_time');   // e.g., 11:00:00
            $table->integer('duration_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_slots');
    }
};
