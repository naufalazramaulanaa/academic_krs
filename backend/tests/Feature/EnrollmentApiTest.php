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

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function getEnrollments(array $query = [])
    {
        return $this->getJson(
            '/api/enrollments?' . http_build_query(
                $query,
                '',
                '&',
                PHP_QUERY_RFC3986
            )
        );
    }

    private function decodeJson(string $value): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }

    private function decodeArray(array $value): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER - contains
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_contains_student_nim(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'student_nim',
                        'operator' => 'contains',
                        'value' => '2026',
                    ],
                ],
            ]),
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
                'meta',
                'links',
            ]);

        foreach ($response->json('data') as $row) {
            $this->assertStringContainsString(
                '2026',
                $row['student_nim']
            );
        }
    }

    public function test_advanced_filter_contains_student_name(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'student_name',
                        'operator' => 'contains',
                        'value' => 'Ahmad',
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertStringContainsStringIgnoringCase(
                'Ahmad',
                $row['student_name']
            );
        }
    }

    public function test_advanced_filter_contains_course_code(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_code',
                        'operator' => 'contains',
                        'value' => 'IF',
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertStringContainsStringIgnoringCase(
                'IF',
                $row['course_code']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER - startsWith
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_starts_with_student_nim(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'student_nim',
                        'operator' => 'startsWith',
                        'value' => '2026',
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertStringStartsWith(
                '2026',
                $row['student_nim']
            );
        }
    }

    public function test_advanced_filter_starts_with_course_code(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_code',
                        'operator' => 'startsWith',
                        'value' => 'IF',
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertStringStartsWith(
                'IF',
                $row['course_code']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER - equal
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_equal_status(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'equal',
                        'value' => 'APPROVED',
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                'APPROVED',
                $row['status']
            );
        }
    }

    public function test_advanced_filter_equal_semester(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'semester',
                        'operator' => 'equal',
                        'value' => 'GANJIL',
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                'GANJIL',
                $row['semester']
            );
        }
    }

    public function test_advanced_filter_equal_course_credits(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'equal',
                        'value' => 3,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                3,
                (int) $row['course_credits']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER - case normalization
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_status_is_case_insensitive(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'equal',
                        'value' => 'approved',
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                'APPROVED',
                $row['status']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER - IN
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_in_status(): void
    {
        $allowedStatuses = [
            'APPROVED',
            'SUBMITTED',
        ];

        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'in',
                        'value' => $allowedStatuses,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertContains(
                $row['status'],
                $allowedStatuses
            );
        }
    }

    public function test_advanced_filter_in_semester(): void
    {
        $allowedSemesters = [
            'GANJIL',
            'GENAP',
        ];

        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'semester',
                        'operator' => 'in',
                        'value' => $allowedSemesters,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertContains(
                $row['semester'],
                $allowedSemesters
            );
        }
    }

    public function test_advanced_filter_in_course_credits(): void
    {
        $allowedCredits = [
            2,
            3,
            4,
        ];

        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'in',
                        'value' => $allowedCredits,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertContains(
                (int) $row['course_credits'],
                $allowedCredits
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER - BETWEEN
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_between_course_credits(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'between',
                        'value' => [2, 4],
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $credits = (int) $row['course_credits'];

            $this->assertGreaterThanOrEqual(2, $credits);
            $this->assertLessThanOrEqual(4, $credits);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER - GT / GTE / LT / LTE
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_gt_course_credits(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'gt',
                        'value' => 3,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertGreaterThan(
                3,
                (int) $row['course_credits']
            );
        }
    }

    public function test_advanced_filter_gte_course_credits(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'gte',
                        'value' => 3,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertGreaterThanOrEqual(
                3,
                (int) $row['course_credits']
            );
        }
    }

    public function test_advanced_filter_lt_course_credits(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'lt',
                        'value' => 4,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertLessThan(
                4,
                (int) $row['course_credits']
            );
        }
    }

    public function test_advanced_filter_lte_course_credits(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'lte',
                        'value' => 4,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertLessThanOrEqual(
                4,
                (int) $row['course_credits']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER - AND
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filters_with_and_logic(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'equal',
                        'value' => 'APPROVED',
                    ],
                    [
                        'field' => 'semester',
                        'operator' => 'equal',
                        'value' => 'GANJIL',
                    ],
                    [
                        'field' => 'course_credits',
                        'operator' => 'gte',
                        'value' => 3,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                'APPROVED',
                $row['status']
            );

            $this->assertSame(
                'GANJIL',
                $row['semester']
            );

            $this->assertGreaterThanOrEqual(
                3,
                (int) $row['course_credits']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER - OR
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filters_with_or_logic(): void
    {
        $allowedStatuses = [
            'APPROVED',
            'REJECTED',
        ];

        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'OR',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'equal',
                        'value' => 'APPROVED',
                    ],
                    [
                        'field' => 'status',
                        'operator' => 'equal',
                        'value' => 'REJECTED',
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertContains(
                $row['status'],
                $allowedStatuses
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | LEGACY + ADVANCED FILTER
    |--------------------------------------------------------------------------
    */

    public function test_legacy_and_advanced_filters_are_combined_with_and(): void
    {
        $response = $this->getEnrollments([
            'status' => 'APPROVED',
            'semester' => 'GANJIL',

            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'gte',
                        'value' => 3,
                    ],
                ],
            ]),
        ]);

        $response->assertOk();

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                'APPROVED',
                $row['status']
            );

            $this->assertSame(
                'GANJIL',
                $row['semester']
            );

            $this->assertGreaterThanOrEqual(
                3,
                (int) $row['course_credits']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED SORT - SINGLE COLUMN
    |--------------------------------------------------------------------------
    */

    public function test_advanced_sort_student_name_ascending(): void
    {
        $response = $this->getEnrollments([
            'sorts' => $this->decodeArray([
                [
                    'field' => 'student_name',
                    'direction' => 'asc',
                ],
            ]),
            'page_size' => 25,
        ]);

        $response->assertOk();

        $rows = $response->json('data');

        $values = array_map(
            fn ($row) => $row['student_name'],
            $rows
        );

        $expected = $values;
        sort($expected, SORT_NATURAL | SORT_FLAG_CASE);

        $this->assertSame(
            $expected,
            $values
        );
    }

    public function test_advanced_sort_course_credits_descending(): void
    {
        $response = $this->getEnrollments([
            'sorts' => $this->decodeArray([
                [
                    'field' => 'course_credits',
                    'direction' => 'desc',
                ],
            ]),
            'page_size' => 25,
        ]);

        $response->assertOk();

        $rows = $response->json('data');

        $values = array_map(
            fn ($row) => (int) $row['course_credits'],
            $rows
        );

        $expected = $values;
        rsort($expected);

        $this->assertSame(
            $expected,
            $values
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED SORT - MULTI COLUMN
    |--------------------------------------------------------------------------
    */

    public function test_advanced_multi_column_sort(): void
{
    $response = $this->getEnrollments([
        'sorts' => $this->decodeArray([
            [
                'field' => 'student_name',
                'direction' => 'asc',
            ],
            [
                'field' => 'course_credits',
                'direction' => 'desc',
            ],
            [
                'field' => 'course_code',
                'direction' => 'asc',
            ],
        ]),
        'page_size' => 50,
    ]);

    $response
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'data',
            'meta',
            'links',
        ]);

    $rows = $response->json('data');

    $this->assertNotEmpty($rows);

    /*
     * Validate that the returned rows follow the same
     * multi-column ordering requested from the API.
     *
     * We use the exact values returned by the API and
     * compare adjacent rows.
     */
    for ($i = 1; $i < count($rows); $i++) {
        $previous = $rows[$i - 1];
        $current = $rows[$i];

        /*
         * First priority:
         * student_name ASC
         */
        $nameComparison = strcasecmp(
            (string) $previous['student_name'],
            (string) $current['student_name']
        );

        if ($nameComparison < 0) {
            continue;
        }

        if ($nameComparison > 0) {
            $this->fail(
                sprintf(
                    'Multi-column sort violation: student_name "%s" appears before "%s".',
                    $previous['student_name'],
                    $current['student_name']
                )
            );
        }

        /*
         * Same student name.
         *
         * Second priority:
         * course_credits DESC
         */
        $previousCredits =
            (int) $previous['course_credits'];

        $currentCredits =
            (int) $current['course_credits'];

        if ($previousCredits > $currentCredits) {
            continue;
        }

        if ($previousCredits < $currentCredits) {
            $this->fail(
                sprintf(
                    'Multi-column sort violation: for student "%s", credits %d appears before %d.',
                    $previous['student_name'],
                    $previousCredits,
                    $currentCredits
                )
            );
        }

        /*
         * Same student name + same credits.
         *
         * Third priority:
         * course_code ASC
         */
        $courseComparison = strcmp(
            (string) $previous['course_code'],
            (string) $current['course_code']
        );

        $this->assertLessThanOrEqual(
            0,
            $courseComparison,
            sprintf(
                'Multi-column sort violation: course_code "%s" appears before "%s".',
                $previous['course_code'],
                $current['course_code']
            )
        );
    }
}

    /*
    |--------------------------------------------------------------------------
    | ADVANCED SORT - ID
    |--------------------------------------------------------------------------
    */

    public function test_advanced_sort_by_id_ascending(): void
    {
        $response = $this->getEnrollments([
            'sorts' => $this->decodeArray([
                [
                    'field' => 'id',
                    'direction' => 'asc',
                ],
            ]),
            'page_size' => 25,
        ]);

        $response->assertOk();

        $rows = $response->json('data');

        $ids = array_map(
            fn ($row) => (int) $row['id'],
            $rows
        );

        $expected = $ids;
        sort($expected);

        $this->assertSame(
            $expected,
            $ids
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ADVANCED FILTER + SORT + PAGINATION
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_sort_and_pagination_work_together(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'equal',
                        'value' => 'APPROVED',
                    ],
                    [
                        'field' => 'course_credits',
                        'operator' => 'gte',
                        'value' => 3,
                    ],
                ],
            ]),

            'sorts' => $this->decodeArray([
                [
                    'field' => 'student_name',
                    'direction' => 'asc',
                ],
                [
                    'field' => 'id',
                    'direction' => 'asc',
                ],
            ]),

            'page' => 2,
            'page_size' => 10,
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'from',
                    'to',
                    'has_more_pages',
                ],
                'links' => [
                    'prev',
                    'next',
                ],
            ]);

        $this->assertSame(
            2,
            $response->json('meta.current_page')
        );

        $this->assertSame(
            10,
            $response->json('meta.per_page')
        );

        foreach ($response->json('data') as $row) {
            $this->assertSame(
                'APPROVED',
                $row['status']
            );

            $this->assertGreaterThanOrEqual(
                3,
                (int) $row['course_credits']
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - INVALID FIELD
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_rejects_unknown_field(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'unknown_field',
                        'operator' => 'equal',
                        'value' => 'test',
                    ],
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters.items.0.field',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - INVALID OPERATOR
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_rejects_unknown_operator(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'contains',
                        'value' => 'APPROVED',
                    ],
                ],
            ]),
        ]);

        /*
         * Status hanya mendukung:
         * equal / in
         */
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters.items.0.operator',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - INVALID LOGIC
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filter_rejects_invalid_logic(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'XOR',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'equal',
                        'value' => 'APPROVED',
                    ],
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters.logic',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - BETWEEN
    |--------------------------------------------------------------------------
    */

    public function test_advanced_between_requires_exactly_two_values(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'between',
                        'value' => [2],
                    ],
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters.items.0.value',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - IN
    |--------------------------------------------------------------------------
    */

    public function test_advanced_in_requires_array(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'in',
                        'value' => 'APPROVED',
                    ],
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters.items.0.value',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - DUPLICATE SORT FIELD
    |--------------------------------------------------------------------------
    */

    public function test_advanced_sort_rejects_duplicate_field(): void
    {
        $response = $this->getEnrollments([
            'sorts' => $this->decodeArray([
                [
                    'field' => 'student_name',
                    'direction' => 'asc',
                ],
                [
                    'field' => 'student_name',
                    'direction' => 'desc',
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'sorts.1.field',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - UNKNOWN SORT FIELD
    |--------------------------------------------------------------------------
    */

    public function test_advanced_sort_rejects_unknown_field(): void
    {
        $response = $this->getEnrollments([
            'sorts' => $this->decodeArray([
                [
                    'field' => 'unknown_field',
                    'direction' => 'asc',
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'sorts.0.field',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - INVALID JSON
    |--------------------------------------------------------------------------
    */

    public function test_advanced_filters_reject_invalid_json(): void
    {
        $response = $this->getJson(
            '/api/enrollments?filters={invalid-json}'
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters',
            ]);
    }

    public function test_advanced_sorts_reject_invalid_json(): void
    {
        $response = $this->getJson(
            '/api/enrollments?sorts={invalid-json}'
        );

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'sorts',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - COURSE CREDITS
    |--------------------------------------------------------------------------
    */

    public function test_advanced_course_credits_rejects_invalid_value(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'course_credits',
                        'operator' => 'equal',
                        'value' => 99,
                    ],
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters.items.0.value',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - ACADEMIC YEAR
    |--------------------------------------------------------------------------
    */

    public function test_advanced_academic_year_rejects_invalid_format(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'academic_year',
                        'operator' => 'equal',
                        'value' => '2026-2027',
                    ],
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters.items.0.value',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - STATUS
    |--------------------------------------------------------------------------
    */

    public function test_advanced_status_rejects_invalid_value(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'status',
                        'operator' => 'equal',
                        'value' => 'INVALID_STATUS',
                    ],
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters.items.0.value',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION - SEMESTER
    |--------------------------------------------------------------------------
    */

    public function test_advanced_semester_rejects_invalid_value(): void
    {
        $response = $this->getEnrollments([
            'filters' => $this->decodeArray([
                'logic' => 'AND',
                'items' => [
                    [
                        'field' => 'semester',
                        'operator' => 'equal',
                        'value' => 'SEMESTER_3',
                    ],
                ],
            ]),
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'filters.items.0.value',
            ]);
    }
}