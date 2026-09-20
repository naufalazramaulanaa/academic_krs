<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEnrollmentRequest;
use App\Http\Requests\UpdateEnrollmentRequest;
use App\Http\Resources\EnrollmentResource;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnrollmentController extends Controller
{
    /**
     * Display a paginated list of enrollments.
     *
     * Supported query parameters:
     *
     * search
     * status
     * semester
     * academic_year
     * sort
     * direction
     * page
     * page_size
     */
    public function index(Request $request): JsonResponse
    {
        /*
         * ============================================
         * ALLOWED SORT COLUMNS
         * ============================================
         *
         * User cannot directly inject a column name
         * into ORDER BY.
         */
        $allowedSorts = [
            'id' => 'e.id',
            'student_nim' => 's.nim',
            'student_name' => 's.name',
            'course_code' => 'c.code',
            'course_name' => 'c.name',
            'semester' => 'e.semester',
            'academic_year' => 'e.academic_year',
            'status' => 'e.status',
            'created_at' => 'e.created_at',
        ];

        /*
         * ============================================
         * BASE QUERY
         * ============================================
         *
         * The enrollment table remains the main
         * dataset. Student and course are joined
         * only for filtering/output.
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
         * ============================================
         * SEARCH
         * ============================================
         *
         * Search:
         * - Student NIM
         * - Student name
         * - Course code
         *
         * IMPORTANT:
         *
         * Do NOT directly apply:
         *
         *     s.nim ILIKE ...
         *     OR s.name ILIKE ...
         *     OR c.code ILIKE ...
         *
         * to the 5M-row enrollment join.
         *
         * Instead:
         *
         * 1. Find matching student IDs.
         * 2. Find matching course IDs.
         * 3. Filter enrollments using those IDs.
         *
         * This allows PostgreSQL to use the enrollment
         * indexes much more efficiently.
         */
        $search = trim(
            (string) $request->query('search', '')
        );

        if ($search !== '') {
            $searchLike = '%' . $search . '%';

            /*
             * Candidate students.
             *
             * This query searches only the students table,
             * which is much smaller than the enrollments
             * table.
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
             * Candidate courses.
             *
             * This query searches only the courses table.
             */
            $courseIds = Course::query()
                ->where(
                    'code',
                    'ILIKE',
                    $searchLike
                )
                ->select('id');

            /*
             * Filter enrollments using the candidate IDs.
             *
             * PostgreSQL can then use:
             *
             * enrollments_student_...
             *
             * or
             *
             * enrollments_course_...
             *
             * instead of evaluating the text search
             * across the entire joined enrollment dataset.
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
         * ============================================
         * STATUS FILTER
         * ============================================
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
         * ============================================
         * SEMESTER FILTER
         * ============================================
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
         * ============================================
         * ACADEMIC YEAR FILTER
         * ============================================
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
         * ============================================
         * SORTING
         * ============================================
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

        /*
         * Invalid sort column:
         * fallback to created_at.
         */
        if (!array_key_exists(
            $sort,
            $allowedSorts
        )) {
            $sort = 'created_at';
        }

        /*
         * Invalid direction:
         * fallback to DESC.
         */
        if (!in_array(
            $direction,
            [
                'asc',
                'desc',
            ],
            true
        )) {
            $direction = 'desc';
        }

        $query->orderBy(
            $allowedSorts[$sort],
            $direction
        );

        /*
         * ============================================
         * STABLE SECONDARY ORDERING
         * ============================================
         *
         * This prevents unstable ordering when multiple
         * rows have the same primary sort value.
         */
        if ($sort !== 'id') {
            $query->orderBy(
                'e.id',
                'desc'
            );
        }

        /*
         * ============================================
         * PAGINATION
         * ============================================
         *
         * Default:
         * 25 rows per page.
         *
         * Maximum:
         * 100 rows per page.
         *
         * IMPORTANT:
         *
         * simplePaginate() is intentionally used instead
         * of paginate().
         *
         * paginate() performs:
         *
         *     SELECT COUNT(*)
         *
         * before fetching the page.
         *
         * With 5,000,000 enrollments, that COUNT was
         * measured at ~1.5 seconds even for the default
         * unfiltered listing.
         *
         * simplePaginate() only fetches the requested page
         * and checks whether another page exists.
         */
        $pageSize = (int) $request->query(
            'page_size',
            25
        );

        $pageSize = max(
            1,
            min($pageSize, 100)
        );

        $result = $query
            ->simplePaginate($pageSize)
            ->withQueryString();

        /*
         * ============================================
         * RESPONSE
         * ============================================
         *
         * simplePaginate() does not provide:
         *
         * - total
         * - last_page
         *
         * Instead we expose:
         *
         * - current_page
         * - per_page
         * - from
         * - to
         * - has_more_pages
         */
        return response()->json([
            'message' =>
                'Enrollments retrieved successfully.',

            'data' => $result->items(),

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
        /*
         * Load related student and course.
         */
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
                     * ==================================
                     * STUDENT
                     * ==================================
                     *
                     * Find by NIM.
                     *
                     * If existing:
                     * update student data.
                     *
                     * If not existing:
                     * create student.
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
                     * ==================================
                     * COURSE
                     * ==================================
                     *
                     * Find by course code.
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
                     * ==================================
                     * ENROLLMENT
                     * ==================================
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

            /*
             * Load relationships for response.
             */
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

            /*
             * Unknown database error:
             * let Laravel handle it.
             */
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

            /*
             * Reload relationships.
             */
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
}