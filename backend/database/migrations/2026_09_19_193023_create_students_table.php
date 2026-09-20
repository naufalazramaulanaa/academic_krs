<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->string('nim', 12)->unique();
            $table->string('name', 100);
            $table->string('email', 150)->unique();

            $table->timestamps();

            $table->index('name', 'students_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};