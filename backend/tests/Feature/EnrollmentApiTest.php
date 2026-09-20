<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentApiTest extends TestCase
{
    use RefreshDatabase;

    /*
     * ============================================
     * TEST DATA
     * ============================================
     */

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Students
         */
        $students = [];

        for ($i = 1; $i <= 5; $i++) {
            $students[] = Student::create([
                'nim' => sprintf(
                    '2026000%d',
                    $i
                ),

                'name' => sprintf(
                    'Mahasiswa Test %03d',
                    $i
                ),

                'email' => sprintf(
                    'student%d@test.example',
                    $i
                ),
            ]);
        }

        /*
         * Courses
         */
        $courses = [];

        for ($i = 1; $i <= 3; $i++) {
            $courses[] = Course::create([
                'code' => sprintf(
                    'IF%03d',
                    $i
                ),

                'name' => sprintf(
                    'Course Test %03d',
                    $i
                ),

                'credits' => 3,
            ]);
        }

        /*
         * Enrollments
         */
        Enrollment::create([
            'student_id' => $students[0]->id,
            'course_id' => $courses[0]->id,
            'academic_year' => '2022/2023',
            'semester' => 'GANJIL',
            'status' => 'DRAFT',
        ]);

        Enrollment::create([
            'student_id' => $students[0]->id,
            'course_id' => $courses[1]->id,
            'academic_year' => '2022/2023',
            'semester' => 'GENAP',
            'status' => 'APPROVED',
        ]);

        Enrollment::create([
            'student_id' => $students[1]->id,
            'course_id' => $courses[0]->id,
            'academic_year' => '2023/2024',
            'semester' => 'GANJIL',
            'status' => 'SUBMITTED',
        ]);

        Enrollment::create([
            'student_id' => $students[2]->id,
            'course_id' => $courses[1]->id,
            'academic_year' => '2024/2025',
            'semester' => 'GENAP',
            'status' => 'REJECTED',
        ]);

        Enrollment::create([
            'student_id' => $students[3]->id,
            'course_id' => $courses[2]->id,
            'academic_year' => '2025/2026',
            'semester' => 'GANJIL',
            'status' => 'APPROVED',
        ]);

        Enrollment::create([
            'student_id' => $students[4]->id,
            'course_id' => $courses[2]->id,
            'academic_year' => '2026/2027',
            'semester' => 'GENAP',
            'status' => 'DRAFT',
        ]);
    }

    /*
     * ============================================
     * INDEX / PAGINATION
     * ============================================
     */

    public function test_can_list_enrollments_with_pagination(): void
    {
        $response = $this->getJson(
            '/api/enrollments?page=1&page_size=2'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Enrollments retrieved successfully.'
            )
            ->assertJsonPath(
                'meta.current_page',
                1
            )
            ->assertJsonPath(
                'meta.per_page',
                2
            )
            ->assertJsonPath(
                'meta.total',
                6
            )
            ->assertJsonCount(
                2,
                'data'
            );
    }

    /*
     * ============================================
     * SEARCH
     * ============================================
     */

    public function test_can_search_by_student_nim(): void
    {
        $response = $this->getJson(
            '/api/enrollments?search=20260001'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                2
            );

        $data = $response->json('data');

        $this->assertCount(2, $data);

        foreach ($data as $row) {
            $this->assertSame(
                '20260001',
                $row['student_nim']
            );
        }
    }

    public function test_can_search_by_student_name(): void
    {
        $response = $this->getJson(
            '/api/enrollments?search=Mahasiswa%20Test%20001'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                2
            );
    }

    public function test_can_search_by_course_code(): void
    {
        $response = $this->getJson(
            '/api/enrollments?search=IF003'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                2
            );
    }

    public function test_unknown_search_returns_empty_result(): void
    {
        $response = $this->getJson(
            '/api/enrollments?search=ZZZZZZZZ'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                0
            )
            ->assertJsonCount(
                0,
                'data'
            );
    }

    /*
     * ============================================
     * FILTERS
     * ============================================
     */

    public function test_can_filter_by_status(): void
    {
        $response = $this->getJson(
            '/api/enrollments?status=APPROVED'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                2
            );

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                'APPROVED',
                $row['status']
            );
        }
    }

    public function test_can_filter_by_semester(): void
    {
        $response = $this->getJson(
            '/api/enrollments?semester=GANJIL'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                3
            );

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                'GANJIL',
                $row['semester']
            );
        }
    }

    public function test_can_filter_by_academic_year(): void
    {
        $response = $this->getJson(
            '/api/enrollments?academic_year=2026/2027'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                1
            );

        $this->assertSame(
            '2026/2027',
            $response->json(
                'data.0.academic_year'
            )
        );
    }

    /*
     * ============================================
     * COMBINED QUERY
     * ============================================
     */

    public function test_can_combine_search_filter_sort_and_pagination(): void
    {
        $response = $this->getJson(
            '/api/enrollments'
            . '?search=IF001'
            . '&semester=GANJIL'
            . '&sort=student_nim'
            . '&direction=asc'
            . '&page=1'
            . '&page_size=1'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'meta.current_page',
                1
            )
            ->assertJsonPath(
                'meta.per_page',
                1
            );

        $this->assertLessThanOrEqual(
            1,
            count($response->json('data'))
        );

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                'GANJIL',
                $row['semester']
            );

            $this->assertSame(
                'IF001',
                $row['course_code']
            );
        }
    }

    /*
     * ============================================
     * SORTING
     * ============================================
     */

    public function test_can_sort_by_student_nim_ascending(): void
    {
        $response = $this->getJson(
            '/api/enrollments'
            . '?sort=student_nim'
            . '&direction=asc'
        );

        $response->assertOk();

        $data = $response->json('data');

        $nims = array_column(
            $data,
            'student_nim'
        );

        $sorted = $nims;

        sort($sorted);

        $this->assertSame(
            $sorted,
            $nims
        );
    }

    public function test_can_sort_by_student_nim_descending(): void
    {
        $response = $this->getJson(
            '/api/enrollments'
            . '?sort=student_nim'
            . '&direction=desc'
        );

        $response->assertOk();

        $data = $response->json('data');

        $nims = array_column(
            $data,
            'student_nim'
        );

        $sorted = $nims;

        rsort($sorted);

        $this->assertSame(
            $sorted,
            $nims
        );
    }

    /*
     * ============================================
     * POST / STORE
     * ============================================
     */

    public function test_can_create_enrollment(): void
    {
        $payload = [
            'student' => [
                'nim' => '20269999',
                'name' => 'Mahasiswa API Test',
                'email' => 'api.test@example.com',
            ],

            'course' => [
                'code' => 'IF999',
                'name' => 'Testing API Laravel',
                'credits' => 3,
            ],

            'academic_year' => '2026/2027',
            'semester' => 'GANJIL',
            'status' => 'DRAFT',
        ];

        $response = $this->postJson(
            '/api/enrollments',
            $payload
        );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Enrollment created successfully.'
            )
            ->assertJsonPath(
                'data.student.nim',
                '20269999'
            )
            ->assertJsonPath(
                'data.course.code',
                'IF999'
            )
            ->assertJsonPath(
                'data.academic_year',
                '2026/2027'
            )
            ->assertJsonPath(
                'data.semester',
                'GANJIL'
            )
            ->assertJsonPath(
                'data.status',
                'DRAFT'
            );

        $this->assertDatabaseHas(
            'students',
            [
                'nim' => '20269999',
            ]
        );

        $this->assertDatabaseHas(
            'courses',
            [
                'code' => 'IF999',
            ]
        );

        $this->assertDatabaseHas(
            'enrollments',
            [
                'academic_year' => '2026/2027',
                'semester' => 'GANJIL',
                'status' => 'DRAFT',
            ]
        );
    }

    /*
     * ============================================
     * VALIDATION
     * ============================================
     */

    public function test_rejects_invalid_enrollment_payload(): void
    {
        $payload = [
            'student' => [
                'nim' => 'ABC',
                'name' => '',
                'email' => 'not-an-email',
            ],

            'course' => [
                'code' => 'BAD',
                'name' => '',
                'credits' => 99,
            ],

            'academic_year' => 'wrong',
            'semester' => 'RANDOM',
            'status' => 'RANDOM',
        ];

        $response = $this->postJson(
            '/api/enrollments',
            $payload
        );

        $response->assertUnprocessable();

        $response->assertJsonValidationErrors([
            'student.nim',
            'student.name',
            'student.email',
            'course.name',
            'course.credits',
            'academic_year',
            'semester',
            'status',
        ]);
    }

    /*
     * ============================================
     * DUPLICATE
     * ============================================
     */

    public function test_rejects_duplicate_enrollment(): void
    {
        $payload = [
            'student' => [
                'nim' => '20269998',
                'name' => 'Duplicate Test',
                'email' => 'duplicate@example.com',
            ],

            'course' => [
                'code' => 'IF998',
                'name' => 'Duplicate Course',
                'credits' => 3,
            ],

            'academic_year' => '2026/2027',
            'semester' => 'GANJIL',
            'status' => 'DRAFT',
        ];

        $first = $this->postJson(
            '/api/enrollments',
            $payload
        );

        $first->assertCreated();

        $second = $this->postJson(
            '/api/enrollments',
            $payload
        );

        $second
            ->assertStatus(409)
            ->assertJsonPath(
                'error',
                'DUPLICATE_ENROLLMENT'
            );
    }

    /*
     * ============================================
     * SHOW
     * ============================================
     */

    public function test_can_show_enrollment(): void
    {
        $enrollment = Enrollment::firstOrFail();

        $response = $this->getJson(
            "/api/enrollments/{$enrollment->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $enrollment->id
            )
            ->assertJsonPath(
                'data.student.nim',
                $enrollment->student->nim
            )
            ->assertJsonPath(
                'data.course.code',
                $enrollment->course->code
            );
    }

    /*
     * ============================================
     * UPDATE
     * ============================================
     */

    public function test_can_update_enrollment(): void
    {
        $enrollment = Enrollment::firstOrFail();

        $payload = [
            'academic_year' => '2026/2027',
            'semester' => 'GENAP',
            'status' => 'SUBMITTED',
        ];

        $response = $this->putJson(
            "/api/enrollments/{$enrollment->id}",
            $payload
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Enrollment updated successfully.'
            )
            ->assertJsonPath(
                'data.academic_year',
                '2026/2027'
            )
            ->assertJsonPath(
                'data.semester',
                'GENAP'
            )
            ->assertJsonPath(
                'data.status',
                'SUBMITTED'
            );

        $this->assertDatabaseHas(
            'enrollments',
            [
                'id' => $enrollment->id,
                'academic_year' => '2026/2027',
                'semester' => 'GENAP',
                'status' => 'SUBMITTED',
            ]
        );
    }

    /*
     * ============================================
     * DELETE
     * ============================================
     */

    public function test_can_delete_enrollment(): void
    {
        $enrollment = Enrollment::firstOrFail();

        $studentId = $enrollment->student_id;
        $courseId = $enrollment->course_id;

        $response = $this->deleteJson(
            "/api/enrollments/{$enrollment->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Enrollment deleted successfully.'
            );

        $this->assertDatabaseMissing(
            'enrollments',
            [
                'id' => $enrollment->id,
            ]
        );

        /*
         * Student must remain.
         */
        $this->assertDatabaseHas(
            'students',
            [
                'id' => $studentId,
            ]
        );

        /*
         * Course must remain.
         */
        $this->assertDatabaseHas(
            'courses',
            [
                'id' => $courseId,
            ]
        );
    }

    /*
     * ============================================
     * 404 AFTER DELETE
     * ============================================
     */

    public function test_deleted_enrollment_returns_404(): void
    {
        $enrollment = Enrollment::firstOrFail();

        $id = $enrollment->id;

        $this->deleteJson(
            "/api/enrollments/{$id}"
        )->assertOk();

        $this->getJson(
            "/api/enrollments/{$id}"
        )->assertNotFound();
    }
}