<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdvancedEnrollmentQueryRequest;
use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use App\Services\EnrollmentQueryService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentController extends Controller
{
    /**
     * Display a paginated list of enrollments.
     *
     * Legacy query parameters:
     *
     * search
     * status
     * semester
     * academic_year
     * sort
     * direction
     * page
     * page_size
     *
     * Advanced query parameters:
     *
     * filters
     * sorts
     */
    public function index(
        AdvancedEnrollmentQueryRequest $request,
        EnrollmentQueryService $queryService
    ): JsonResponse {
        /*
         * ==========================================================
         * ALLOWED LEGACY SORT COLUMNS
         * ==========================================================
         *
         * Never allow raw user input to become an SQL column.
         */
        $allowedSorts = [
            'id' => 'e.id',
            'student_nim' => 's.nim',
            'student_name' => 's.name',
            'student_email' => 's.email',
            'course_code' => 'c.code',
            'course_name' => 'c.name',
            'course_credits' => 'c.credits',
            'semester' => 'e.semester',
            'academic_year' => 'e.academic_year',
            'status' => 'e.status',
            'created_at' => 'e.created_at',
        ];

        /*
         * ==========================================================
         * BASE QUERY
         * ==========================================================
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
            ]);

        /*
         * ==========================================================
         * LEGACY SEARCH
         * ==========================================================
         *
         * Search:
         *
         * - student NIM
         * - student name
         * - course code
         *
         * IMPORTANT:
         *
         * We intentionally DO NOT use:
         *
         * WHERE
         *     s.nim ILIKE ...
         *     OR s.name ILIKE ...
         *     OR c.code ILIKE ...
         *
         * directly against the 5M enrollment join.
         *
         * Instead:
         *
         * 1. Search students independently.
         * 2. Search courses independently.
         * 3. Filter enrollments by resulting IDs.
         */
        $search = trim(
            (string) $request->query(
                'search',
                ''
            )
        );

        if ($search !== '') {
            $searchLike = '%' . $search . '%';

            /*
             * Candidate student IDs.
             */
            $studentIds = Student::query()
                ->where(function ($q) use ($searchLike) {
                    $q->where(
                        'nim',
                        'ILIKE',
                        $searchLike
                    )
                        ->orWhere(
                            'name',
                            'ILIKE',
                            $searchLike
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
                    $searchLike
                )
                ->select('id');

            /*
             * Filter enrollments by candidate IDs.
             */
            $query->where(function ($q) use (
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
            });
        }

        /*
         * ==========================================================
         * LEGACY STATUS FILTER
         * ==========================================================
         */
        $status = $request->query('status');

        if (
            is_string($status)
            && in_array(
                $status,
                [
                    'DRAFT',
                    'SUBMITTED',
                    'APPROVED',
                    'REJECTED',
                ],
                true
            )
        ) {
            $query->where(
                'e.status',
                $status
            );
        }

        /*
         * ==========================================================
         * LEGACY SEMESTER FILTER
         * ==========================================================
         */
        $semester = $request->query('semester');

        if (
            is_string($semester)
            && in_array(
                $semester,
                [
                    'GANJIL',
                    'GENAP',
                ],
                true
            )
        ) {
            $query->where(
                'e.semester',
                $semester
            );
        }

        /*
         * ==========================================================
         * LEGACY ACADEMIC YEAR FILTER
         * ==========================================================
         */
        $academicYear = $request->query(
            'academic_year'
        );

        if (
            is_string($academicYear)
            && preg_match(
                '/^[0-9]{4}\/[0-9]{4}$/',
                $academicYear
            )
        ) {
            $query->where(
                'e.academic_year',
                $academicYear
            );
        }

        /*
         * ==========================================================
         * ADVANCED FILTER
         * ==========================================================
         *
         * Example:
         *
         * filters={
         *   "logic":"AND",
         *   "items":[
         *     {
         *       "field":"student_nim",
         *       "operator":"contains",
         *       "value":"2026"
         *     },
         *     {
         *       "field":"status",
         *       "operator":"in",
         *       "value":["APPROVED","SUBMITTED"]
         *     }
         *   ]
         * }
         *
         * Legacy filters above remain compatible.
         *
         * Therefore:
         *
         * legacy filter AND advanced filter
         */
        $queryService->applyFilters(
            $query,
            $request->input('filters')
        );

        /*
         * ==========================================================
         * SORTING
         * ==========================================================
         *
         * If advanced sorts are provided:
         *
         *     sorts=[...]
         *
         * then advanced sorting takes priority.
         *
         * Otherwise use the existing legacy:
         *
         *     sort
         *     direction
         */
        $advancedSorts = $request->input('sorts');

        if (
            is_array($advancedSorts)
            && count($advancedSorts) > 0
        ) {
            $queryService->applySorting(
                $query,
                $advancedSorts
            );
        } else {
            /*
             * ======================================================
             * LEGACY SORT
             * ======================================================
             */
            $sort = $request->query(
                'sort',
                'created_at'
            );

            $direction = strtolower(
                (string) $request->query(
                    'direction',
                    'desc'
                )
            );

            if (
                !is_string($sort)
                || !array_key_exists(
                    $sort,
                    $allowedSorts
                )
            ) {
                $sort = 'created_at';
            }

            if (
                !in_array(
                    $direction,
                    [
                        'asc',
                        'desc',
                    ],
                    true
                )
            ) {
                $direction = 'desc';
            }

            $query->orderBy(
                $allowedSorts[$sort],
                $direction
            );

            /*
             * Stable ordering.
             */
            if ($sort !== 'id') {
                $query->orderBy(
                    'e.id',
                    'desc'
                );
            }
        }

        /*
         * ==========================================================
         * PAGINATION
         * ==========================================================
         *
         * simplePaginate is intentional.
         *
         * We do NOT use paginate() here because paginate()
         * executes COUNT(*) and that can become expensive on
         * a 5M-row dataset.
         */
        $pageSize = (int) $request->query(
            'page_size',
            25
        );

        $pageSize = max(
            1,
            min(
                $pageSize,
                100
            )
        );

        $result = $query
            ->simplePaginate(
                $pageSize
            )
            ->withQueryString();

        /*
         * ==========================================================
         * RESPONSE
         * ==========================================================
         */
        return response()->json([
            'message' =>
            'Enrollments retrieved successfully.',

            'data' =>
            $result->items(),

            'meta' => [
                'current_page' =>
                $result->currentPage(),

                'per_page' =>
                $result->perPage(),

                'from' =>
                $result->firstItem(),

                'to' =>
                $result->lastItem(),

                'has_more_pages' =>
                $result->hasMorePages(),
            ],

            'links' => [
                'prev' =>
                $result->previousPageUrl(),

                'next' =>
                $result->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Display a single enrollment.
     */
    public function show(
        Enrollment $enrollment
    ): EnrollmentResource {
        $enrollment->load([
            'student',
            'course',
        ]);

        return new EnrollmentResource(
            $enrollment
        );
    }

    /**
     * Create a new enrollment.
     *
     * Student + Course + Enrollment are handled
     * inside one database transaction.
     */
    public function store(
        StoreEnrollmentRequest $request
    ): JsonResponse {
        try {
            $enrollment = DB::transaction(
                function () use ($request) {
                    /*
                     * Student
                     */
                    $student = Student::updateOrCreate(
                        [
                            'nim' =>
                            $request->input(
                                'student.nim'
                            ),
                        ],
                        [
                            'name' =>
                            $request->input(
                                'student.name'
                            ),

                            'email' =>
                            $request->input(
                                'student.email'
                            ),
                        ]
                    );

                    /*
                     * Course
                     */
                    $course = Course::updateOrCreate(
                        [
                            'code' =>
                            $request->input(
                                'course.code'
                            ),
                        ],
                        [
                            'name' =>
                            $request->input(
                                'course.name'
                            ),

                            'credits' =>
                            $request->integer(
                                'course.credits'
                            ),
                        ]
                    );

                    /*
                     * Enrollment
                     */
                    return Enrollment::create([
                        'student_id' =>
                        $student->id,

                        'course_id' =>
                        $course->id,

                        'academic_year' =>
                        $request->input(
                            'academic_year'
                        ),

                        'semester' =>
                        $request->input(
                            'semester'
                        ),

                        'status' =>
                        $request->input(
                            'status'
                        ),
                    ]);
                }
            );

            $enrollment->load([
                'student',
                'course',
            ]);

            return response()->json([
                'message' =>
                'Enrollment created successfully.',

                'data' =>
                new EnrollmentResource(
                    $enrollment
                ),
            ], 201);
        } catch (QueryException $e) {
            /*
             * PostgreSQL duplicate key violation.
             */
            if ($e->getCode() === '23505') {
                return response()->json([
                    'message' =>
                    'Enrollment already exists for this student, course, academic year, and semester.',

                    'error' =>
                    'DUPLICATE_ENROLLMENT',
                ], 409);
            }

            throw $e;
        }
    }

    /**
     * Update an enrollment.
     */
    public function update(
        UpdateEnrollmentRequest $request,
        Enrollment $enrollment
    ): JsonResponse {
        try {
            DB::transaction(
                function () use (
                    $request,
                    $enrollment
                ) {
                    $enrollment->update([
                        'academic_year' =>
                        $request->input(
                            'academic_year'
                        ),

                        'semester' =>
                        $request->input(
                            'semester'
                        ),

                        'status' =>
                        $request->input(
                            'status'
                        ),
                    ]);
                }
            );

            $enrollment->load([
                'student',
                'course',
            ]);

            return response()->json([
                'message' =>
                'Enrollment updated successfully.',

                'data' =>
                new EnrollmentResource(
                    $enrollment
                ),
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() === '23505') {
                return response()->json([
                    'message' =>
                    'The updated enrollment would create a duplicate KRS.',

                    'error' =>
                    'DUPLICATE_ENROLLMENT',
                ], 409);
            }

            throw $e;
        }
    }

    /**
     * Delete an enrollment.
     *
     * Student and course are NOT deleted.
     */
    public function destroy(
        Enrollment $enrollment
    ): JsonResponse {
        $enrollment->delete();

        return response()->json([
            'message' =>
            'Enrollment deleted successfully.',
        ]);
    }

    public function export(
        AdvancedEnrollmentQueryRequest $request,
        EnrollmentQueryService $queryService
    ): StreamedResponse {
        /*
     * ==========================================================
     * EXECUTION SETTINGS
     * ==========================================================
     *
     * Exporting 5M rows can take a long time.
     */
        set_time_limit(0);

        /*
     * Laravel query logging should not accumulate
     * millions of queries/objects in memory.
     */
        DB::connection()->disableQueryLog();

        /*
     * ==========================================================
     * ALLOWED SORT COLUMNS
     * ==========================================================
     */
        $allowedSorts = [
            'id' => 'e.id',
            'student_nim' => 's.nim',
            'student_name' => 's.name',
            'student_email' => 's.email',
            'course_code' => 'c.code',
            'course_name' => 'c.name',
            'course_credits' => 'c.credits',
            'semester' => 'e.semester',
            'academic_year' => 'e.academic_year',
            'status' => 'e.status',
            'created_at' => 'e.created_at',
        ];

        /*
     * ==========================================================
     * BASE QUERY
     * ==========================================================
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
            ]);

        /*
     * ==========================================================
     * LEGACY SEARCH
     * ==========================================================
     
     */
        $search = trim(
            (string) $request->query(
                'search',
                ''
            )
        );

        if ($search !== '') {
            $searchLike = '%' . $search . '%';

            $studentIds = Student::query()
                ->where(function ($q) use ($searchLike) {
                    $q->where(
                        'nim',
                        'ILIKE',
                        $searchLike
                    )
                        ->orWhere(
                            'name',
                            'ILIKE',
                            $searchLike
                        );
                })
                ->select('id');

            $courseIds = Course::query()
                ->where(
                    'code',
                    'ILIKE',
                    $searchLike
                )
                ->select('id');

            $query->where(function ($q) use (
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
            });
        }

        /*
     * ==========================================================
     * LEGACY STATUS FILTER
     * ==========================================================
     */
        $status = $request->query('status');

        if (
            is_string($status)
            && in_array(
                $status,
                [
                    'DRAFT',
                    'SUBMITTED',
                    'APPROVED',
                    'REJECTED',
                ],
                true
            )
        ) {
            $query->where(
                'e.status',
                $status
            );
        }

        /*
     * ==========================================================
     * LEGACY SEMESTER FILTER
     * ==========================================================
     */
        $semester = $request->query('semester');

        if (
            is_string($semester)
            && in_array(
                $semester,
                [
                    'GANJIL',
                    'GENAP',
                ],
                true
            )
        ) {
            $query->where(
                'e.semester',
                $semester
            );
        }

        /*
     * ==========================================================
     * LEGACY ACADEMIC YEAR FILTER
     * ==========================================================
     */
        $academicYear = $request->query(
            'academic_year'
        );

        if (
            is_string($academicYear)
            && preg_match(
                '/^[0-9]{4}\/[0-9]{4}$/',
                $academicYear
            )
        ) {
            $query->where(
                'e.academic_year',
                $academicYear
            );
        }

        /*
     * ==========================================================
     * ADVANCED FILTER
     * ==========================================================
     *
     * Reuse the same service as the normal list endpoint.
     */
        $queryService->applyFilters(
            $query,
            $request->input('filters')
        );

        /*
     * ==========================================================
     * SORTING
     * ==========================================================
     *
     * Advanced sorting takes priority.
     */
        $advancedSorts = $request->input(
            'sorts'
        );

        if (
            is_array($advancedSorts)
            && count($advancedSorts) > 0
        ) {
            $queryService->applySorting(
                $query,
                $advancedSorts
            );
        } else {
            /*
         * Legacy sorting.
         */
            $sort = $request->query(
                'sort',
                'created_at'
            );

            $direction = strtolower(
                (string) $request->query(
                    'direction',
                    'desc'
                )
            );

            if (
                !is_string($sort)
                || !array_key_exists(
                    $sort,
                    $allowedSorts
                )
            ) {
                $sort = 'created_at';
            }

            if (
                !in_array(
                    $direction,
                    [
                        'asc',
                        'desc',
                    ],
                    true
                )
            ) {
                $direction = 'desc';
            }

            $query->orderBy(
                $allowedSorts[$sort],
                $direction
            );

            /*
         * Stable ordering.
         */
            if ($sort !== 'id') {
                $query->orderBy(
                    'e.id',
                    'desc'
                );
            }
        }

        /*
     * ==========================================================
     * CSV RESPONSE
     * ==========================================================
     */
        $filename =
            'enrollments-' .
            now()->format('Ymd-His') .
            '.csv';

        return response()->streamDownload(
            function () use ($query) {
                $handle = fopen(
                    'php://output',
                    'w'
                );

                if ($handle === false) {
                    return;
                }

                /*
             * UTF-8 BOM.
             *
             * This helps Microsoft Excel recognize
             * UTF-8 CSV correctly.
             */
                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                /*
             * ==================================================
             * CSV HEADER
             * ==================================================
             */
                fputcsv(
                    $handle,
                    [
                        'ID',
                        'Student NIM',
                        'Student Name',
                        'Student Email',
                        'Course Code',
                        'Course Name',
                        'Course Credits',
                        'Semester',
                        'Academic Year',
                        'Status',
                        'Created At',
                        'Updated At',
                    ]
                );

                /*
             * ==================================================
             * STREAM ROWS
             * ==================================================
             *
             * cursor() prevents loading the complete
             * 5M-row result into PHP memory.
             */
                $count = 0;

                foreach (
                    $query->cursor()
                    as $row
                ) {
                    fputcsv(
                        $handle,
                        [
                            $row->id,
                            $row->student_nim,
                            $row->student_name,
                            $row->student_email,
                            $row->course_code,
                            $row->course_name,
                            $row->course_credits,
                            $row->semester,
                            $row->academic_year,
                            $row->status,
                            $row->created_at,
                            $row->updated_at,
                        ]
                    );

                    $count++;

                    /*
                 * Flush output periodically.
                 *
                 * This prevents the server/output buffer
                 * from holding a huge amount of CSV data.
                 */
                    if (
                        $count % 1000 === 0
                    ) {
                        if (
                            ob_get_level() > 0
                        ) {
                            ob_flush();
                        }

                        flush();
                    }
                }

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' =>
                'text/csv; charset=UTF-8',

                'Content-Disposition' =>
                'attachment; filename="' .
                    $filename .
                    '"',

                'Cache-Control' =>
                'no-store, no-cache, must-revalidate',

                'Pragma' =>
                'no-cache',

                'X-Accel-Buffering' =>
                'no',
            ]
        );
    }
}
