<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SeedLargeDataset extends Command
{
    protected $signature = 'db:seed-large
                            {--enrollments=5000000 : Number of enrollments to generate}
                            {--students=100000 : Number of students to generate}
                            {--courses=1000 : Number of courses to generate}
                            {--batch=5000 : Insert batch size}
                            {--force : Allow destructive database reset}';

    protected $description = 'Generate a large PostgreSQL dataset for performance benchmarking';

    /*
     * ============================================
     * NATURAL INDONESIAN NAME COMPONENTS
     * ============================================
     */

    private array $firstNames = [
        'Ahmad',
        'Aisyah',
        'Andi',
        'Annisa',
        'Arif',
        'Bagas',
        'Bambang',
        'Bayu',
        'Bella',
        'Bima',
        'Citra',
        'Dani',
        'Dimas',
        'Dinda',
        'Doni',
        'Eka',
        'Fadil',
        'Fajar',
        'Farhan',
        'Fauzan',
        'Fitri',
        'Galih',
        'Gilang',
        'Hadi',
        'Hana',
        'Hendra',
        'Ika',
        'Ilham',
        'Indah',
        'Intan',
        'Irfan',
        'Joko',
        'Kevin',
        'Laila',
        'Maya',
        'Melati',
        'Muhammad',
        'Nadia',
        'Nanda',
        'Naufal',
        'Nia',
        'Nur',
        'Putri',
        'Rafi',
        'Rahma',
        'Raka',
        'Rani',
        'Rasya',
        'Rizky',
        'Salsabila',
        'Sari',
        'Siti',
        'Taufik',
        'Tiara',
        'Vina',
        'Wahyu',
        'Yani',
        'Yoga',
        'Yusuf',
        'Zahra',
    ];

    private array $middleNames = [
        'Aditya',
        'Akbar',
        'Alam',
        'Ananda',
        'Angga',
        'Arya',
        'Bagus',
        'Bintang',
        'Cahya',
        'Daffa',
        'Darmawan',
        'Dwi',
        'Fauzi',
        'Hafiz',
        'Hakim',
        'Haris',
        'Jaya',
        'Kurnia',
        'Maulana',
        'Pratama',
        'Putra',
        'Ramadhan',
        'Rama',
        'Reza',
        'Ridho',
        'Rizal',
        'Saputra',
        'Setiawan',
        'Surya',
        'Wicaksono',
    ];

    private array $lastNames = [
        'Adiputra',
        'Anwar',
        'Firmansyah',
        'Hidayat',
        'Irawan',
        'Iskandar',
        'Kurniawan',
        'Kusuma',
        'Mahendra',
        'Maulana',
        'Nugraha',
        'Permana',
        'Prakoso',
        'Pratama',
        'Putra',
        'Rahman',
        'Ramadhan',
        'Saputra',
        'Santoso',
        'Sari',
        'Setiawan',
        'Siregar',
        'Suharto',
        'Susanto',
        'Syahputra',
        'Wijaya',
        'Wibowo',
        'Yulianto',
        'Zulkarnain',
        'Gunawan',
    ];

    private array $coursePrefixes = [
        'Pemrograman',
        'Sistem',
        'Analisis',
        'Manajemen',
        'Rekayasa',
        'Teknologi',
        'Jaringan',
        'Basis Data',
        'Keamanan',
        'Kecerdasan',
        'Statistika',
        'Matematika',
        'Arsitektur',
        'Pengembangan',
        'Perancangan',
    ];

    private array $courseSubjects = [
        'Web',
        'Mobile',
        'Basis Data',
        'Informasi',
        'Perangkat Lunak',
        'Komputer',
        'Algoritma',
        'Cloud Computing',
        'Artificial Intelligence',
        'Machine Learning',
        'Data Science',
        'Sistem Informasi',
        'Jaringan Komputer',
        'Keamanan Informasi',
        'Interaksi Manusia dan Komputer',
        'Proyek',
        'Bisnis',
        'Digital',
        'Terapan',
        'Lanjut',
    ];

    /*
     * ============================================
     * MAIN COMMAND
     * ============================================
     */

    public function handle(): int
    {
        $studentCount = (int) $this->option('students');
        $courseCount = (int) $this->option('courses');
        $enrollmentCount = (int) $this->option('enrollments');
        $batchSize = (int) $this->option('batch');

        if ($studentCount <= 0) {
            $this->error('Students must be greater than 0.');

            return self::FAILURE;
        }

        if ($courseCount <= 0) {
            $this->error('Courses must be greater than 0.');

            return self::FAILURE;
        }

        if ($enrollmentCount <= 0) {
            $this->error('Enrollments must be greater than 0.');

            return self::FAILURE;
        }

        if ($batchSize <= 0 || $batchSize > 10000) {
            $this->error(
                'Batch size must be between 1 and 10000.'
            );

            return self::FAILURE;
        }

        /*
         * We intentionally require --force because this command
         * destroys the current students/courses/enrollments data.
         */
        if (!$this->option('force')) {
            $this->error(
                'This command will DELETE existing academic data.'
            );

            $this->error(
                'Run again with --force if you really want to continue.'
            );

            return self::FAILURE;
        }

        $this->newLine();

        $this->info(
            '============================================'
        );

        $this->info(
            ' LARGE DATASET SEEDER'
        );

        $this->info(
            '============================================'
        );

        $this->table(
            [
                'Dataset',
                'Rows',
            ],
            [
                ['Students', number_format($studentCount)],
                ['Courses', number_format($courseCount)],
                ['Enrollments', number_format($enrollmentCount)],
                ['Batch size', number_format($batchSize)],
            ]
        );

        $this->newLine();

        $startedAt = microtime(true);

        try {
            /*
             * ============================================
             * RESET DATA
             * ============================================
             */

            $this->info(
                'Resetting students, courses and enrollments...'
            );

            DB::statement(
                'TRUNCATE TABLE enrollments, courses, students RESTART IDENTITY CASCADE'
            );

            /*
             * ============================================
             * STUDENTS
             * ============================================
             */

            $studentIds = $this->seedStudents(
                $studentCount,
                $batchSize
            );

            /*
             * ============================================
             * COURSES
             * ============================================
             */

            $courseIds = $this->seedCourses(
                $courseCount,
                $batchSize
            );

            /*
             * ============================================
             * ENROLLMENTS
             * ============================================
             */

            $this->seedEnrollments(
                $enrollmentCount,
                $studentCount,
                $courseCount,
                $studentIds,
                $courseIds,
                $batchSize
            );

            /*
             * ============================================
             * SUMMARY
             * ============================================
             */

            $elapsed = microtime(true) - $startedAt;

            $this->newLine();

            $this->info(
                '============================================'
            );

            $this->info(
                ' LARGE DATASET SEEDING COMPLETED'
            );

            $this->info(
                '============================================'
            );

            $this->table(
                [
                    'Table',
                    'Count',
                ],
                [
                    [
                        'students',
                        number_format(
                            DB::table('students')->count()
                        ),
                    ],
                    [
                        'courses',
                        number_format(
                            DB::table('courses')->count()
                        ),
                    ],
                    [
                        'enrollments',
                        number_format(
                            DB::table('enrollments')->count()
                        ),
                    ],
                ]
            );

            $this->info(
                sprintf(
                    'Elapsed time: %.2f seconds',
                    $elapsed
                )
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();

            $this->error(
                'Large dataset seeding failed.'
            );

            $this->error(
                $e->getMessage()
            );

            return self::FAILURE;
        }
    }

    /*
     * ============================================
     * STUDENTS
     * ============================================
     */

    private function seedStudents(
        int $studentCount,
        int $batchSize
    ): array {
        $this->newLine();

        $this->info(
            'Generating students...'
        );

        // $allIds = [];

        $bar = $this->output->createProgressBar(
            $studentCount
        );

        $bar->start();

        $batch = [];

        $now = now();

        for ($i = 1; $i <= $studentCount; $i++) {
            $batch[] = [
                'nim' => sprintf(
                    '2026%06d',
                    $i
                ),

                'name' => $this->generateStudentName(
                    $i
                ),

                'email' => sprintf(
                    'student%06d@example.test',
                    $i
                ),

                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('students')->insert(
                    $batch
                );

                $batch = [];

                $bar->advance(
                    $batchSize
                );
            }
        }

        if ($batch !== []) {
            DB::table('students')->insert(
                $batch
            );

            $bar->advance(
                count($batch)
            );
        }

        $bar->finish();

        $this->newLine();

        /*
         * Retrieve generated IDs.
         *
         * IDs start at 1 because the table was truncated
         * with RESTART IDENTITY.
         */
        $ids = DB::table('students')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->info(
            'Students generated: ' .
            number_format(count($ids))
        );

        return $ids;
    }

    /*
     * ============================================
     * COURSES
     * ============================================
     */

    private function seedCourses(
        int $courseCount,
        int $batchSize
    ): array {
        $this->newLine();

        $this->info(
            'Generating courses...'
        );

        $bar = $this->output->createProgressBar(
            $courseCount
        );

        $bar->start();

        $batch = [];

        $now = now();

        for ($i = 1; $i <= $courseCount; $i++) {
            $batch[] = [
                'code' => sprintf(
                    'IF%04d',
                    $i
                ),

                'name' => $this->generateCourseName(
                    $i
                ),

                'credits' => match ($i % 4) {
                    0 => 4,
                    1 => 2,
                    default => 3,
                },

                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('courses')->insert(
                    $batch
                );

                $bar->advance(
                    count($batch)
                );

                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('courses')->insert(
                $batch
            );

            $bar->advance(
                count($batch)
            );
        }

        $bar->finish();

        $this->newLine();

        $ids = DB::table('courses')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->info(
            'Courses generated: ' .
            number_format(count($ids))
        );

        return $ids;
    }

    /*
     * ============================================
     * ENROLLMENTS
     * ============================================
     */

    private function seedEnrollments(
        int $enrollmentCount,
        int $studentCount,
        int $courseCount,
        array $studentIds,
        array $courseIds,
        int $batchSize
    ): void {
        $this->newLine();

        $this->info(
            'Generating enrollments...'
        );

        $bar = $this->output->createProgressBar(
            $enrollmentCount
        );

        $bar->start();

        $statuses = [
            'DRAFT',
            'SUBMITTED',
            'APPROVED',
            'REJECTED',
        ];

        $semesters = [
            'GANJIL',
            'GENAP',
        ];

        $batch = [];

        $now = now();

        /*
         * Each period contains exactly one enrollment
         * per student.
         *
         * Example:
         *
         * 100,000 students
         * x
         * 50 periods
         * =
         * 5,000,000 enrollments
         */
        for ($i = 0; $i < $enrollmentCount; $i++) {
            $studentIndex = $i % $studentCount;

            $periodIndex = intdiv(
                $i,
                $studentCount
            );

            /*
             * Courses are distributed across students.
             *
             * The multiplier 37 is relatively prime to
             * 1000, giving a good deterministic spread.
             */
            $courseIndex = (
                ($studentIndex * 37)
                + $periodIndex
            ) % $courseCount;

            /*
             * 2 semesters per academic year.
             */
            $yearIndex = intdiv(
                $periodIndex,
                2
            );

            $startYear = 2022 + $yearIndex;

            $academicYear = sprintf(
                '%d/%d',
                $startYear,
                $startYear + 1
            );

            $semester = $semesters[
                $periodIndex % 2
            ];

            $status = $statuses[
                $i % count($statuses)
            ];

            $batch[] = [
                'student_id' => $studentIds[
                    $studentIndex
                ],

                'course_id' => $courseIds[
                    $courseIndex
                ],

                'academic_year' => $academicYear,

                'semester' => $semester,

                'status' => $status,

                'created_at' => $now,

                'updated_at' => $now,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('enrollments')->insert(
                    $batch
                );

                $bar->advance(
                    count($batch)
                );

                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('enrollments')->insert(
                $batch
            );

            $bar->advance(
                count($batch)
            );
        }

        $bar->finish();

        $this->newLine();

        $this->info(
            'Enrollments generated: ' .
            number_format(
                $enrollmentCount
            )
        );
    }

    /*
     * ============================================
     * NATURAL STUDENT NAME
     * ============================================
     */

    private function generateStudentName(
        int $number
    ): string {
        $firstCount = count(
            $this->firstNames
        );

        $middleCount = count(
            $this->middleNames
        );

        $lastCount = count(
            $this->lastNames
        );

        /*
         * Deterministic combination.
         *
         * No random generator is used.
         * This makes benchmark data reproducible.
         */
        $first = $this->firstNames[
            ($number - 1) % $firstCount
        ];

        $middle = $this->middleNames[
            (intdiv($number - 1, $firstCount))
            % $middleCount
        ];

        $last = $this->lastNames[
            (intdiv(
                $number - 1,
                $firstCount * $middleCount
            )) % $lastCount
        ];

        /*
         * Occasionally use a two-part name,
         * so the generated names don't all have
         * exactly the same structure.
         */
        if ($number % 5 === 0) {
            return "{$first} {$last}";
        }

        if ($number % 7 === 0) {
            return "{$first} {$middle}";
        }

        return "{$first} {$middle} {$last}";
    }

    /*
     * ============================================
     * COURSE NAME
     * ============================================
     */

    private function generateCourseName(
        int $number
    ): string {
        $prefix = $this->coursePrefixes[
            ($number - 1)
            % count($this->coursePrefixes)
        ];

        $subject = $this->courseSubjects[
            (intdiv(
                $number - 1,
                count($this->coursePrefixes)
            ))
            % count($this->courseSubjects)
        ];

        return "{$prefix} {$subject}";
    }
}