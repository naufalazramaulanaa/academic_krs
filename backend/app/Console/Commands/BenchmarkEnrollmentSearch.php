<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class BenchmarkEnrollmentSearch extends Command
{
    protected $signature = 'benchmark:enrollment-search
                            {search=2026000019 : Search keyword to benchmark}
                            {--limit=25 : Number of rows to fetch}';

    protected $description = 'Diagnose enrollment search performance using EXPLAIN ANALYZE';

    public function handle(): int
    {
        $search = trim(
            (string) $this->argument('search')
        );

        $limit = max(
            1,
            min(
                (int) $this->option('limit'),
                100
            )
        );

        if ($search === '') {
            $this->error('Search keyword cannot be empty.');

            return self::FAILURE;
        }

        $this->newLine();

        $this->info(
            'Enrollment Search Performance Diagnostic'
        );

        $this->line(
            '========================================='
        );

        $this->newLine();

        $this->line(
            'Search : ' . $search
        );

        $this->line(
            'Limit  : ' . $limit
        );

        $this->line(
            'Database: ' . DB::connection()->getDatabaseName()
        );

        $this->newLine();

        /*
         * PostgreSQL diagnostic setting.
         *
         * track_io_timing allows EXPLAIN ANALYZE to show
         * time spent reading data from storage/cache.
         *
         * This does not change query results.
         */
        try {
            DB::statement(
                'SET track_io_timing = on'
            );
        } catch (Throwable $e) {
            $this->warn(
                'Could not enable track_io_timing: ' .
                $e->getMessage()
            );
        }

        /*
         * ============================================
         * STEP 1
         * ============================================
         *
         * Student candidate query.
         *
         * This is equivalent to the candidate student
         * query used by EnrollmentController@index.
         */
        $this->runStudentCandidateExplain(
            $search
        );

        /*
         * ============================================
         * STEP 2
         * ============================================
         *
         * Course candidate query.
         */
        $this->runCourseCandidateExplain(
            $search
        );

        /*
         * ============================================
         * STEP 3
         * ============================================
         *
         * Final Laravel-style enrollment query.
         *
         * This is the important one because the current
         * benchmark showed approximately 716 ms for:
         *
         * search=2026000019
         */
        $this->runFinalEnrollmentExplain(
            $search,
            $limit
        );

        /*
         * ============================================
         * STEP 4
         * ============================================
         *
         * Compare against a student-driven query.
         *
         * This intentionally starts from matching
         * students and then joins into enrollments.
         *
         * We previously benchmarked this approach
         * manually and obtained a much lower execution
         * time.
         */
        $this->runStudentDrivenExplain(
            $search,
            $limit
        );

        $this->newLine();

        $this->info(
            'Diagnostic completed.'
        );

        $this->newLine();

        $this->line(
            'Focus on the "Final enrollment query" and compare'
        );

        $this->line(
            'it with the "Student-driven query".'
        );

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Explain the student candidate query.
     */
    private function runStudentCandidateExplain(
        string $search
    ): void {
        $this->section(
            '1. Student candidate query'
        );

        $pattern = '%' . $search . '%';

        $query = Student::query()
            ->where(function (Builder $q) use ($pattern) {
                $q->where(
                    'nim',
                    'ILIKE',
                    $pattern
                )
                    ->orWhere(
                        'name',
                        'ILIKE',
                        $pattern
                    );
            })
            ->select('id');

        $this->printQueryInfo(
            $query,
            'student candidate'
        );

        $this->printExplain(
            $query,
            'Student candidate EXPLAIN ANALYZE'
        );

        /*
         * Also show how many candidate student IDs
         * were actually found.
         */
        $start = hrtime(true);

        $ids = $query
            ->pluck('id');

        $elapsedMs =
            (hrtime(true) - $start)
            / 1_000_000;

        $this->line('');

        $this->line(
            'Matching student IDs : ' .
            $ids->count()
        );

        $this->line(
            'Laravel execution    : ' .
            number_format(
                $elapsedMs,
                3
            ) .
            ' ms'
        );

        if ($ids->isNotEmpty()) {
            $this->line(
                'First IDs            : ' .
                $ids->take(10)->implode(', ')
            );
        }
    }

    /**
     * Explain the course candidate query.
     */
    private function runCourseCandidateExplain(
        string $search
    ): void {
        $this->section(
            '2. Course candidate query'
        );

        $pattern = '%' . $search . '%';

        $query = Course::query()
            ->where(
                'code',
                'ILIKE',
                $pattern
            )
            ->select('id');

        $this->printQueryInfo(
            $query,
            'course candidate'
        );

        $this->printExplain(
            $query,
            'Course candidate EXPLAIN ANALYZE'
        );

        $start = hrtime(true);

        $ids = $query
            ->pluck('id');

        $elapsedMs =
            (hrtime(true) - $start)
            / 1_000_000;

        $this->line('');

        $this->line(
            'Matching course IDs : ' .
            $ids->count()
        );

        $this->line(
            'Laravel execution   : ' .
            number_format(
                $elapsedMs,
                3
            ) .
            ' ms'
        );

        if ($ids->isNotEmpty()) {
            $this->line(
                'First IDs           : ' .
                $ids->take(10)->implode(', ')
            );
        }
    }

    /**
     * Explain the final query generated by the
     * current candidate-ID implementation.
     */
    private function runFinalEnrollmentExplain(
        string $search,
        int $limit
    ): void {
        $this->section(
            '3. Final enrollment query'
        );

        $pattern = '%' . $search . '%';

        /*
         * Candidate student IDs.
         */
        $studentIds = Student::query()
            ->where(function (Builder $q) use ($pattern) {
                $q->where(
                    'nim',
                    'ILIKE',
                    $pattern
                )
                    ->orWhere(
                        'name',
                        'ILIKE',
                        $pattern
                    );
            })
            ->select('id');

        /*
         * Candidate course IDs.
         */
        $courseIds = Course::query()
            ->where(
                'code',
                'ILIKE',
                $pattern
            )
            ->select('id');

        /*
         * This mirrors the current controller:
         *
         * WHERE
         *     e.student_id IN (student candidates)
         *     OR
         *     e.course_id IN (course candidates)
         */
        $query = Enrollment::query()
            ->from('enrollments as e')
            ->join(
                'students as s',
                's.id',
                '=',
                'e.student_id'
            )
            ->join(
                'courses as c',
                'c.id',
                '=',
                'e.course_id'
            )
            ->select([
                'e.id',
                'e.student_id',
                'e.course_id',
                'e.academic_year',
                'e.semester',
                'e.status',
                'e.created_at',
                'e.updated_at',

                's.nim as student_nim',
                's.name as student_name',
                's.email as student_email',

                'c.code as course_code',
                'c.name as course_name',
                'c.credits as course_credits',
            ])
            ->where(function (Builder $q) use (
                $studentIds,
                $courseIds
            ) {
                $q->whereIn(
                    'e.student_id',
                    $studentIds
                )
                    ->orWhereIn(
                        'e.course_id',
                        $courseIds
                    );
            })
            ->orderBy(
                'e.created_at',
                'desc'
            )
            ->orderBy(
                'e.id',
                'desc'
            )
            ->limit($limit);

        $this->printQueryInfo(
            $query,
            'final enrollment query'
        );

        $this->printExplain(
            $query,
            'Final enrollment EXPLAIN ANALYZE'
        );

        /*
         * Measure actual Laravel query execution.
         */
        $start = hrtime(true);

        $rows = $query->get();

        $elapsedMs =
            (hrtime(true) - $start)
            / 1_000_000;

        $this->line('');

        $this->line(
            'Rows returned       : ' .
            $rows->count()
        );

        $this->line(
            'Laravel execution   : ' .
            number_format(
                $elapsedMs,
                3
            ) .
            ' ms'
        );
    }

    /**
     * Explain a student-driven query.
     *
     * This provides a comparison against the query
     * shape that previously benchmarked much faster.
     */
    private function runStudentDrivenExplain(
        string $search,
        int $limit
    ): void {
        $this->section(
            '4. Student-driven comparison query'
        );

        $pattern = '%' . $search . '%';

        /*
         * Important:
         *
         * The matching students become the starting
         * relation.
         *
         * Then PostgreSQL joins directly into enrollments
         * by student_id.
         */
        $query = DB::table(
            DB::raw(
                '(
                    SELECT id
                    FROM students
                    WHERE nim ILIKE ?
                       OR name ILIKE ?
                ) as ms'
            )
        )
            ->setBindings([
                $pattern,
                $pattern,
            ])
            ->join(
                'enrollments as e',
                'e.student_id',
                '=',
                'ms.id'
            )
            ->join(
                'students as s',
                's.id',
                '=',
                'e.student_id'
            )
            ->join(
                'courses as c',
                'c.id',
                '=',
                'e.course_id'
            )
            ->select([
                'e.id',
                'e.student_id',
                'e.course_id',
                'e.academic_year',
                'e.semester',
                'e.status',
                'e.created_at',
                'e.updated_at',

                's.nim as student_nim',
                's.name as student_name',
                's.email as student_email',

                'c.code as course_code',
                'c.name as course_name',
                'c.credits as course_credits',
            ])
            ->orderBy(
                'e.created_at',
                'desc'
            )
            ->orderBy(
                'e.id',
                'desc'
            )
            ->limit($limit);

        $this->printQueryInfo(
            $query,
            'student-driven query'
        );

        $this->printExplain(
            $query,
            'Student-driven EXPLAIN ANALYZE'
        );

        $start = hrtime(true);

        $rows = $query->get();

        $elapsedMs =
            (hrtime(true) - $start)
            / 1_000_000;

        $this->line('');

        $this->line(
            'Rows returned       : ' .
            $rows->count()
        );

        $this->line(
            'Laravel execution   : ' .
            number_format(
                $elapsedMs,
                3
            ) .
            ' ms'
        );
    }

    /**
     * Print generated SQL and bindings.
     */
    private function printQueryInfo(
        $query,
        string $name
    ): void {
        $this->line('');

        $this->line(
            'Generated SQL (' .
            $name .
            '):'
        );

        $this->line(
            $query->toSql()
        );

        $this->line('');

        $this->line(
            'Bindings:'
        );

        $this->line(
            json_encode(
                $query->getBindings(),
                JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
            )
        );
    }

    /**
     * Execute EXPLAIN ANALYZE against the generated
     * SQL and print the complete PostgreSQL plan.
     */
    private function printExplain(
        $query,
        string $title
    ): void {
        $this->line('');

        $this->info(
            $title
        );

        $this->line(
            str_repeat(
                '-',
                70
            )
        );

        $sql = $query->toSql();

        $bindings = $query->getBindings();

        /*
         * EXPLAIN options:
         *
         * ANALYZE  = actually execute the query
         * BUFFERS  = show buffer/cache usage
         * TIMING   = show node timing
         * SUMMARY  = show total execution time
         * FORMAT TEXT = human-readable PostgreSQL plan
         */
        $explainSql = sprintf(
            'EXPLAIN (
                ANALYZE,
                BUFFERS,
                TIMING,
                SUMMARY,
                FORMAT TEXT
            ) %s',
            $sql
        );

        try {
            $start = hrtime(true);

            $rows = DB::select(
                $explainSql,
                $bindings
            );

            $elapsedMs =
                (hrtime(true) - $start)
                / 1_000_000;

            foreach ($rows as $row) {
                /*
                 * PostgreSQL returns the EXPLAIN line
                 * under the "QUERY PLAN" column.
                 */
                $line = $row->{'QUERY PLAN'}
                    ?? reset(
                        get_object_vars($row)
                    );

                $this->line(
                    (string) $line
                );
            }

            $this->line('');

            $this->line(
                'EXPLAIN wall time: ' .
                number_format(
                    $elapsedMs,
                    3
                ) .
                ' ms'
            );

        } catch (Throwable $e) {

            $this->error(
                'EXPLAIN failed:'
            );

            $this->error(
                $e->getMessage()
            );
        }
    }

    /**
     * Simple section header.
     */
    private function section(
        string $title
    ): void {
        $this->newLine();

        $this->info(
            $title
        );

        $this->line(
            str_repeat(
                '=',
                70
            )
        );
    }
}