<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->restrictOnDelete();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

            $table->string('academic_year', 9);

            $table->string('semester', 10);

            $table->string('status', 10);

            $table->timestamps();

            /*
             * Prevent duplicate KRS.
             */
            $table->unique(
                [
                    'student_id',
                    'course_id',
                    'academic_year',
                    'semester',
                ],
                'enrollments_unique_krs'
            );

            /*
             * Query indexes.
             */

            $table->index(
                [
                    'student_id',
                    'academic_year',
                    'semester',
                ],
                'enrollments_student_period_idx'
            );

            $table->index(
                [
                    'course_id',
                    'academic_year',
                    'semester',
                ],
                'enrollments_course_period_idx'
            );

            $table->index(
                'status',
                'enrollments_status_idx'
            );

            $table->index(
                'semester',
                'enrollments_semester_idx'
            );

            $table->index(
                'academic_year',
                'enrollments_academic_year_idx'
            );

            $table->index(
                'created_at',
                'enrollments_created_at_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};