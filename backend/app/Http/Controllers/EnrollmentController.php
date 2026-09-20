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
         * PostgreSQL ILIKE is used so search is
         * case-insensitive.
         */

        $search = trim(
            (string) $request->query('search', '')
        );

        if ($search !== '') {
            $searchLike = '%' . $search . '%';

            $query->where(function ($q) use ($searchLike) {
                $q->where(
                    's.nim',
                    'ILIKE',
                    $searchLike
                )

                ->orWhere(
                    's.name',
                    'ILIKE',
                    $searchLike
                )

                ->orWhere(
                    'c.code',
                    'ILIKE',
                    $searchLike
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
         * Stable secondary ordering.
         *
         * This is useful when many records have
         * exactly the same sorting value.
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
         */

        $pageSize = (int) $request->query(
            'page_size',
            25
        );

        $pageSize = max(
            1,
            min($pageSize, 100)
        );

        /*
         * IMPORTANT:
         *
         * paginate() executes SQL with LIMIT/OFFSET.
         *
         * It does NOT retrieve all 1000 rows
         * and then paginate them in PHP.
         */
        $result = $query
            ->paginate($pageSize)
            ->withQueryString();

        /*
         * ============================================
         * RESPONSE
         * ============================================
         */

        return response()->json([
            'message' =>
                'Enrollments retrieved successfully.',

            'data' => $result->items(),

            'meta' => [
                'current_page' =>
                    $result->currentPage(),

                'last_page' =>
                    $result->lastPage(),

                'per_page' =>
                    $result->perPage(),

                'total' =>
                    $result->total(),

                'from' =>
                    $result->firstItem(),

                'to' =>
                    $result->lastItem(),
            ],

            'links' => [
                'first' =>
                    $result->url(1),

                'last' =>
                    $result->url(
                        $result->lastPage()
                    ),

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