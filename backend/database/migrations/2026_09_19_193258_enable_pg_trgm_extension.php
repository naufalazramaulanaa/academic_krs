<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE EXTENSION IF NOT EXISTS pg_trgm'
        );

        DB::statement(
            'CREATE INDEX students_nim_trgm_idx
             ON students
             USING gin (nim gin_trgm_ops)'
        );

        DB::statement(
            'CREATE INDEX students_name_trgm_idx
             ON students
             USING gin (name gin_trgm_ops)'
        );

        DB::statement(
            'CREATE INDEX courses_code_trgm_idx
             ON courses
             USING gin (code gin_trgm_ops)'
        );

        DB::statement(
            'CREATE INDEX courses_name_trgm_idx
             ON courses
             USING gin (name gin_trgm_ops)'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS students_nim_trgm_idx'
        );

        DB::statement(
            'DROP INDEX IF EXISTS students_name_trgm_idx'
        );

        DB::statement(
            'DROP INDEX IF EXISTS courses_code_trgm_idx'
        );

        DB::statement(
            'DROP INDEX IF EXISTS courses_name_trgm_idx'
        );
    }
};