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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number')->unique();
            $table->string('building')->nullable();
            $table->unsignedInteger('total_rows')->default(5);
            $table->unsignedInteger('total_cols')->default(4);
            $table->unsignedInteger('total_seats')->nullable()->comment('Override capacity if row*col differs');
            $table->json('disabled_seats')->nullable(); 
        
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
