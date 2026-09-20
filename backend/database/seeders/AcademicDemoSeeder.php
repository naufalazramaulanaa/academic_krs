<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AcademicDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        /*
         * Clean demo data.
         *
         * Development only.
         */
        DB::statement(
            'TRUNCATE TABLE enrollments, courses, students RESTART IDENTITY CASCADE'
        );

        /*
         * ============================================
         * STUDENTS
         * ============================================
         */

        $students = [];

        for ($i = 1; $i <= 100; $i++) {
            $students[] = [
                'nim' => sprintf(
                    '2026%06d',
                    $i
                ),

                'name' => sprintf(
                    'Mahasiswa Demo %03d',
                    $i
                ),

                'email' => sprintf(
                    'student%03d@example.test',
                    $i
                ),

                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('students')->insert($students);

        /*
         * Retrieve student IDs.
         */
        $studentIds = DB::table('students')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        /*
         * ============================================
         * COURSES
         * ============================================
         */

        $courseNames = [
            'Pemrograman Web',
            'Basis Data',
            'Algoritma dan Struktur Data',
            'Pemrograman Berorientasi Objek',
            'Sistem Operasi',
            'Jaringan Komputer',
            'Rekayasa Perangkat Lunak',
            'Analisis dan Perancangan Sistem',
            'Kecerdasan Buatan',
            'Machine Learning',
            'Keamanan Informasi',
            'Pemrograman Mobile',
            'Cloud Computing',
            'Interaksi Manusia dan Komputer',
            'Manajemen Proyek TI',
            'Sistem Informasi',
            'Statistika',
            'Matematika Diskrit',
            'Bahasa Inggris',
            'Etika Profesi',
        ];

        $courses = [];

        foreach ($courseNames as $index => $courseName) {
            $number = $index + 1;

            $courses[] = [
                'code' => sprintf(
                    'IF%03d',
                    $number
                ),

                'name' => $courseName,

                'credits' => match (true) {
                    $number % 5 === 0 => 4,
                    $number % 3 === 0 => 3,
                    default => 2,
                },

                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('courses')->insert($courses);

        /*
         * Retrieve course IDs.
         */
        $courseIds = DB::table('courses')
            ->orderBy('id')
            ->pluck('id')
            ->values()
            ->all();

        /*
         * ============================================
         * ENROLLMENTS
         * ============================================
         *
         * Total:
         * - 5 academic years
         * - 200 enrollments per year
         * - 100 GANJIL
         * - 100 GENAP
         *
         * Total = 1000 rows
         *
         * Unique constraint:
         * student_id
         * course_id
         * academic_year
         * semester
         */

        $enrollments = [];

        $statuses = [
            'DRAFT',
            'SUBMITTED',
            'APPROVED',
            'REJECTED',
        ];

        $academicYears = [
            '2022/2023',
            '2023/2024',
            '2024/2025',
            '2025/2026',
            '2026/2027',
        ];

        foreach ($academicYears as $yearIndex => $academicYear) {

            /*
             * ========================================
             * GANJIL
             * ========================================
             */

            for ($j = 0; $j < 100; $j++) {

                $studentIndex = $j % 100;

                $courseIndex = ($j * 11) % 20;

                $enrollments[] = [
                    'student_id' => $studentIds[$studentIndex],
                    'course_id' => $courseIds[$courseIndex],
                    'academic_year' => $academicYear,
                    'semester' => 'GANJIL',
                    'status' => $statuses[
                        ($j + $yearIndex) % count($statuses)
                    ],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            /*
             * ========================================
             * GENAP
             * ========================================
             */

            for ($j = 0; $j < 100; $j++) {

                $studentIndex = $j % 100;

                $courseIndex = (($j * 11) + 1) % 20;

                $enrollments[] = [
                    'student_id' => $studentIds[$studentIndex],
                    'course_id' => $courseIds[$courseIndex],
                    'academic_year' => $academicYear,
                    'semester' => 'GENAP',
                    'status' => $statuses[
                        ($j + $yearIndex + 1) % count($statuses)
                    ],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            /*
             * Insert 200 rows per academic year.
             */
            DB::table('enrollments')->insert($enrollments);

            $enrollments = [];
        }

        /*
         * ============================================
         * RESULT
         * ============================================
         */

        $this->command?->info(
            'Academic demo data created successfully.'
        );

        $this->command?->info(
            'Students: ' .
            DB::table('students')->count()
        );

        $this->command?->info(
            'Courses: ' .
            DB::table('courses')->count()
        );

        $this->command?->info(
            'Enrollments: ' .
            DB::table('enrollments')->count()
        );
    }
}