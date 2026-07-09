<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('importer');
            $table->integer('total_rows');
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows_count')->default(0);
            $table->timestamps();
        });

        Schema::create('failed_import_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('import_id');
            $table->longText('data');
            $table->longText('validation_errors')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_import_rows');
        Schema::dropIfExists('imports');
    }
};
