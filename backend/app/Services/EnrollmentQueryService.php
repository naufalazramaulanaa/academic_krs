<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class EnrollmentQueryService
{
    private const FILTER_COLUMNS = [
        'student_nim' => 's.nim',
        'student_name' => 's.name',
        'student_email' => 's.email',
        'course_code' => 'c.code',
        'course_name' => 'c.name',
        'course_credits' => 'c.credits',
        'academic_year' => 'e.academic_year',
        'semester' => 'e.semester',
        'status' => 'e.status',
    ];

    private const SORT_COLUMNS = [
        'id' => 'e.id',
        'student_nim' => 's.nim',
        'student_name' => 's.name',
        'student_email' => 's.email',
        'course_code' => 'c.code',
        'course_name' => 'c.name',
        'course_credits' => 'c.credits',
        'academic_year' => 'e.academic_year',
        'semester' => 'e.semester',
        'status' => 'e.status',
        'created_at' => 'e.created_at',
    ];

    /**
     * Apply advanced filters.
     *
     * Example:
     *
     * [
     *     'logic' => 'AND',
     *     'items' => [
     *         [
     *             'field' => 'student_name',
     *             'operator' => 'contains',
     *             'value' => 'Ahmad',
     *         ],
     *         [
     *             'field' => 'status',
     *             'operator' => 'equal',
     *             'value' => 'APPROVED',
     *         ],
     *     ],
     * ]
     */
    public function applyFilters(
        Builder $query,
        ?array $filterGroup
    ): Builder {
        if (
            !is_array($filterGroup)
            || empty($filterGroup['items'])
            || !is_array($filterGroup['items'])
        ) {
            return $query;
        }

        $logic = strtoupper(
            (string) ($filterGroup['logic'] ?? 'AND')
        );

        if (!in_array($logic, ['AND', 'OR'], true)) {
            $logic = 'AND';
        }

        $items = array_values($filterGroup['items']);

        $query->where(function (Builder $group) use ($items, $logic) {
            $firstCondition = true;

            foreach ($items as $filter) {
                if (!is_array($filter)) {
                    continue;
                }

                $boolean = $firstCondition
                    ? 'and'
                    : ($logic === 'OR' ? 'or' : 'and');

                $this->applyFilter(
                    $group,
                    $filter,
                    $boolean
                );

                $firstCondition = false;
            }
        });

        return $query;
    }

    /**
     * Apply one filter condition.
     */
    private function applyFilter(
        Builder $query,
        array $filter,
        string $boolean
    ): void {
        $field = $filter['field'] ?? null;
        $operator = $filter['operator'] ?? null;
        $value = $filter['value'] ?? null;

        if (
            !is_string($field)
            || !isset(self::FILTER_COLUMNS[$field])
        ) {
            return;
        }

        $column = self::FILTER_COLUMNS[$field];

        switch ($operator) {
            case 'contains':
                $pattern = '%'
                    . $this->escapeLike((string) $value)
                    . '%';

                $this->whereLike(
                    $query,
                    $column,
                    $pattern,
                    $boolean
                );

                break;

            case 'startsWith':
                $pattern =
                    $this->escapeLike((string) $value) . '%';

                $this->whereLike(
                    $query,
                    $column,
                    $pattern,
                    $boolean
                );

                break;

            case 'equal':
                $this->whereBoolean(
                    $query,
                    $column,
                    '=',
                    $this->normalizeValue(
                        $field,
                        $value
                    ),
                    $boolean
                );

                break;

            case 'in':
                if (
                    !is_array($value)
                    || empty($value)
                ) {
                    return;
                }

                $values = array_map(
                    fn ($item) => $this->normalizeValue(
                        $field,
                        $item
                    ),
                    $value
                );

                $this->whereInBoolean(
                    $query,
                    $column,
                    $values,
                    $boolean
                );

                break;

            case 'between':
                if (
                    !is_array($value)
                    || count($value) !== 2
                ) {
                    return;
                }

                $values = array_map(
                    fn ($item) => $this->normalizeValue(
                        $field,
                        $item
                    ),
                    array_values($value)
                );

                $this->whereBetweenBoolean(
                    $query,
                    $column,
                    $values,
                    $boolean
                );

                break;

            case 'gt':
            case 'gte':
            case 'lt':
            case 'lte':
                $sqlOperator = match ($operator) {
                    'gt' => '>',
                    'gte' => '>=',
                    'lt' => '<',
                    'lte' => '<=',
                };

                $this->whereBoolean(
                    $query,
                    $column,
                    $sqlOperator,
                    $this->normalizeValue(
                        $field,
                        $value
                    ),
                    $boolean
                );

                break;
        }
    }

    /**
     * Apply advanced multi-column sorting.
     */
    public function applySorting(
        Builder $query,
        ?array $sorts
    ): Builder {
        if (
            !is_array($sorts)
            || empty($sorts)
        ) {
            return $query;
        }

        $containsId = false;

        foreach ($sorts as $sort) {
            if (!is_array($sort)) {
                continue;
            }

            $field = $sort['field'] ?? null;

            $direction = strtolower(
                (string) ($sort['direction'] ?? 'asc')
            );

            if (
                !is_string($field)
                || !isset(self::SORT_COLUMNS[$field])
            ) {
                continue;
            }

            if (
                !in_array(
                    $direction,
                    ['asc', 'desc'],
                    true
                )
            ) {
                $direction = 'asc';
            }

            $query->orderBy(
                self::SORT_COLUMNS[$field],
                $direction
            );

            if ($field === 'id') {
                $containsId = true;
            }
        }

        /**
         * Stable deterministic tie-breaker.
         */
        if (!$containsId) {
            $query->orderBy('e.id', 'desc');
        }

        return $query;
    }

    /**
     * Case-insensitive LIKE/ILIKE condition.
     *
     * Uses Laravel query builder binding instead of raw SQL.
     */
    private function whereLike(
        Builder $query,
        string $column,
        string $pattern,
        string $boolean
    ): void {
        if ($boolean === 'or') {
            $query->orWhere(
                $column,
                'ILIKE',
                $pattern
            );

            return;
        }

        $query->where(
            $column,
            'ILIKE',
            $pattern
        );
    }

    /**
     * Apply normal comparison.
     */
    private function whereBoolean(
        Builder $query,
        string $column,
        string $operator,
        mixed $value,
        string $boolean
    ): void {
        if ($boolean === 'or') {
            $query->orWhere(
                $column,
                $operator,
                $value
            );

            return;
        }

        $query->where(
            $column,
            $operator,
            $value
        );
    }

    /**
     * Apply IN condition.
     */
    private function whereInBoolean(
        Builder $query,
        string $column,
        array $values,
        string $boolean
    ): void {
        if ($boolean === 'or') {
            $query->orWhereIn(
                $column,
                $values
            );

            return;
        }

        $query->whereIn(
            $column,
            $values
        );
    }

    /**
     * Apply BETWEEN condition.
     */
    private function whereBetweenBoolean(
        Builder $query,
        string $column,
        array $values,
        string $boolean
    ): void {
        if ($boolean === 'or') {
            $query->orWhereBetween(
                $column,
                $values
            );

            return;
        }

        $query->whereBetween(
            $column,
            $values
        );
    }

    /**
     * Normalize filter values according to field type.
     */
    private function normalizeValue(
        string $field,
        mixed $value
    ): mixed {
        if ($field === 'course_credits') {
            return (int) $value;
        }

        if (
            in_array(
                $field,
                ['semester', 'status'],
                true
            )
            && is_string($value)
        ) {
            return strtoupper($value);
        }

        return $value;
    }

    /**
     * Escape LIKE wildcard characters.
     *
     * %, _, and \ are treated as literal characters.
     */
    private function escapeLike(
        string $value
    ): string {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }
}