<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdvancedEnrollmentQueryRequest extends FormRequest
{
    /**
     * ============================================================
     * ALLOWED FILTER FIELDS
     * ============================================================
     */
    public const FILTER_FIELDS = [
        'student_nim',
        'student_name',
        'student_email',

        'course_code',
        'course_name',
        'course_credits',

        'academic_year',
        'semester',
        'status',
    ];

    /**
     * ============================================================
     * ALLOWED SORT FIELDS
     * ============================================================
     */
    public const SORT_FIELDS = [
        'id',

        'student_nim',
        'student_name',
        'student_email',

        'course_code',
        'course_name',
        'course_credits',

        'academic_year',
        'semester',
        'status',

        'created_at',
    ];

    /**
     * ============================================================
     * ALLOWED OPERATORS PER FIELD
     * ============================================================
     */
    public const FIELD_OPERATORS = [
        /*
         * Text fields
         */
        'student_nim' => [
            'contains',
            'startsWith',
            'equal',
            'in',
        ],

        'student_name' => [
            'contains',
            'startsWith',
            'equal',
            'in',
        ],

        'student_email' => [
            'contains',
            'startsWith',
            'equal',
            'in',
        ],

        'course_code' => [
            'contains',
            'startsWith',
            'equal',
            'in',
        ],

        'course_name' => [
            'contains',
            'startsWith',
            'equal',
            'in',
        ],

        /*
         * Numeric field
         */
        'course_credits' => [
            'equal',
            'in',
            'between',
            'gt',
            'gte',
            'lt',
            'lte',
        ],

        /*
         * Academic year
         */
        'academic_year' => [
            'equal',
            'in',
            'between',
        ],

        /*
         * Enum fields
         */
        'semester' => [
            'equal',
            'in',
        ],

        'status' => [
            'equal',
            'in',
        ],
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * ============================================================
     * PREPARE QUERY PARAMETERS
     * ============================================================
     *
     * Convert JSON query parameters:
     *
     * filters='{"logic":"AND","items":[...]}'
     *
     * into:
     *
     * filters => [...]
     */
    protected function prepareForValidation(): void
    {
        $this->decodeJsonParameter('filters');
        $this->decodeJsonParameter('sorts');
    }

    /**
     * Decode a JSON query parameter safely.
     */
    private function decodeJsonParameter(
        string $parameter
    ): void {
        $value = $this->query($parameter);

        /*
         * Parameter is not present or is already an array.
         */
        if (!is_string($value)) {
            return;
        }

        /*
         * Empty parameter.
         */
        if (trim($value) === '') {
            return;
        }

        $decoded = json_decode(
            $value,
            true
        );

        /*
         * Invalid JSON.
         */
        if (
            json_last_error() !== JSON_ERROR_NONE
        ) {
            $this->merge([
                "__invalid_{$parameter}" => true,
            ]);

            return;
        }

        /*
         * Valid JSON.
         */
        $this->merge([
            $parameter => $decoded,
        ]);
    }

    /**
     * ============================================================
     * STRUCTURAL VALIDATION
     * ============================================================
     */
    public function rules(): array
    {
        return [
            /*
             * ====================================================
             * ADVANCED FILTER
             * ====================================================
             */
            'filters' => [
                'nullable',
                'array',
            ],

            'filters.logic' => [
                'required_with:filters',
                'string',
                'in:AND,OR',
            ],

            'filters.items' => [
                'required_with:filters',
                'array',
                'min:1',
                'max:20',
            ],

            'filters.items.*' => [
                'required',
                'array',
            ],

            'filters.items.*.field' => [
                'required',
                'string',
                'max:50',
            ],

            'filters.items.*.operator' => [
                'required',
                'string',
                'max:30',
            ],

            'filters.items.*.value' => [
                'required',
            ],

            /*
             * ====================================================
             * ADVANCED SORT
             * ====================================================
             */
            'sorts' => [
                'nullable',
                'array',
                'max:5',
            ],

            'sorts.*' => [
                'required',
                'array',
            ],

            'sorts.*.field' => [
                'required',
                'string',
                'max:50',
            ],

            'sorts.*.direction' => [
                'required',
                'string',
                'in:asc,desc',
            ],

            /*
             * ====================================================
             * INTERNAL FLAGS
             * ====================================================
             */
            '__invalid_filters' => [
                'nullable',
                'boolean',
            ],

            '__invalid_sorts' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * ============================================================
     * SEMANTIC VALIDATION
     * ============================================================
     */
    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator) {
                $this->validateAdvancedFilters(
                    $validator
                );

                $this->validateAdvancedSorts(
                    $validator
                );
            }
        );
    }

    /**
     * ============================================================
     * VALIDATE ADVANCED FILTERS
     * ============================================================
     */
    private function validateAdvancedFilters(
        Validator $validator
    ): void {
        /*
         * Invalid JSON.
         */
        if (
            $this->input('__invalid_filters') === true
        ) {
            $validator->errors()->add(
                'filters',
                'The filters parameter must contain valid JSON.'
            );

            return;
        }

        $filters = $this->input('filters');

        /*
         * No advanced filters.
         */
        if (
            !is_array($filters)
            || !isset($filters['items'])
        ) {
            return;
        }

        /*
         * Validate logic explicitly.
         */
        if (
            isset($filters['logic'])
            && !in_array(
                $filters['logic'],
                ['AND', 'OR'],
                true
            )
        ) {
            $validator->errors()->add(
                'filters.logic',
                'Filter logic must be AND or OR.'
            );
        }

        foreach (
            $filters['items'] as $index => $item
        ) {
            /*
             * Prevent malformed item from causing
             * PHP errors during semantic validation.
             */
            if (!is_array($item)) {
                continue;
            }

            $field = $item['field'] ?? null;
            $operator = $item['operator'] ?? null;
            $value = $item['value'] ?? null;

            /*
             * Validate field.
             */
            if (
                !is_string($field)
                || !in_array(
                    $field,
                    self::FILTER_FIELDS,
                    true
                )
            ) {
                $validator->errors()->add(
                    "filters.items.{$index}.field",
                    'Unsupported filter field.'
                );

                continue;
            }

            /*
             * Validate operator.
             */
            $allowedOperators =
                self::FIELD_OPERATORS[$field];

            if (
                !is_string($operator)
                || !in_array(
                    $operator,
                    $allowedOperators,
                    true
                )
            ) {
                $validator->errors()->add(
                    "filters.items.{$index}.operator",
                    "Operator '{$operator}' is not supported for {$field}."
                );

                continue;
            }

            /*
             * Validate value according to field/operator.
             */
            $this->validateFilterValue(
                $validator,
                $index,
                $field,
                $operator,
                $value
            );
        }
    }

    /**
     * ============================================================
     * VALIDATE FILTER VALUE
     * ============================================================
     */
    private function validateFilterValue(
        Validator $validator,
        int|string $index,
        string $field,
        string $operator,
        mixed $value
    ): void {
        $attribute =
            "filters.items.{$index}.value";

        /*
         * ========================================================
         * IN
         * ========================================================
         */
        if ($operator === 'in') {
            if (
                !is_array($value)
                || count($value) < 1
                || count($value) > 50
            ) {
                $validator->errors()->add(
                    $attribute,
                    'The in operator requires an array containing 1-50 values.'
                );

                return;
            }

            foreach ($value as $itemValue) {
                $this->validateSingleValue(
                    $validator,
                    $attribute,
                    $field,
                    $itemValue
                );
            }

            return;
        }

        /*
         * ========================================================
         * BETWEEN
         * ========================================================
         */
        if ($operator === 'between') {
            if (
                !is_array($value)
                || count($value) !== 2
            ) {
                $validator->errors()->add(
                    $attribute,
                    'The between operator requires exactly two values.'
                );

                return;
            }

            foreach ($value as $itemValue) {
                $this->validateSingleValue(
                    $validator,
                    $attribute,
                    $field,
                    $itemValue
                );
            }

            return;
        }

        /*
         * ========================================================
         * SCALAR OPERATORS
         * ========================================================
         */
        if (is_array($value)) {
            $validator->errors()->add(
                $attribute,
                'This operator requires a single value.'
            );

            return;
        }

        $this->validateSingleValue(
            $validator,
            $attribute,
            $field,
            $value
        );
    }

    /**
     * ============================================================
     * VALIDATE SINGLE VALUE
     * ============================================================
     */
    private function validateSingleValue(
        Validator $validator,
        string $attribute,
        string $field,
        mixed $value
    ): void {
        /*
         * ========================================================
         * COURSE CREDITS
         * ========================================================
         */
        if ($field === 'course_credits') {
            if (
                filter_var(
                    $value,
                    FILTER_VALIDATE_INT
                ) === false
            ) {
                $validator->errors()->add(
                    $attribute,
                    'Course credits must be an integer.'
                );

                return;
            }

            $credits = (int) $value;

            if (
                $credits < 1
                || $credits > 6
            ) {
                $validator->errors()->add(
                    $attribute,
                    'Course credits must be between 1 and 6.'
                );
            }

            return;
        }

        /*
         * ========================================================
         * ACADEMIC YEAR
         * ========================================================
         */
        if ($field === 'academic_year') {
            if (
                !is_string($value)
                || !preg_match(
                    '/^[0-9]{4}\/[0-9]{4}$/',
                    $value
                )
            ) {
                $validator->errors()->add(
                    $attribute,
                    'Academic year must use YYYY/YYYY format.'
                );
            }

            return;
        }

        /*
         * ========================================================
         * SEMESTER
         * ========================================================
         */
        if ($field === 'semester') {
            if (
                !is_string($value)
                || !in_array(
                    strtoupper($value),
                    [
                        'GANJIL',
                        'GENAP',
                    ],
                    true
                )
            ) {
                $validator->errors()->add(
                    $attribute,
                    'Semester must be GANJIL or GENAP.'
                );
            }

            return;
        }

        /*
         * ========================================================
         * STATUS
         * ========================================================
         */
        if ($field === 'status') {
            if (
                !is_string($value)
                || !in_array(
                    strtoupper($value),
                    [
                        'DRAFT',
                        'SUBMITTED',
                        'APPROVED',
                        'REJECTED',
                    ],
                    true
                )
            ) {
                $validator->errors()->add(
                    $attribute,
                    'Invalid enrollment status.'
                );
            }

            return;
        }

        /*
         * ========================================================
         * TEXT FIELDS
         * ========================================================
         */
        if (
            !is_string($value)
            || trim($value) === ''
            || mb_strlen($value) > 200
        ) {
            $validator->errors()->add(
                $attribute,
                'Filter value must be a non-empty string with maximum 200 characters.'
            );
        }
    }

    /**
     * ============================================================
     * VALIDATE ADVANCED SORTS
     * ============================================================
     */
    private function validateAdvancedSorts(
        Validator $validator
    ): void {
        /*
         * Invalid JSON.
         */
        if (
            $this->input('__invalid_sorts') === true
        ) {
            $validator->errors()->add(
                'sorts',
                'The sorts parameter must contain valid JSON.'
            );

            return;
        }

        $sorts = $this->input('sorts');

        if (!is_array($sorts)) {
            return;
        }

        /*
         * Prevent duplicate sort fields.
         */
        $fields = [];

        foreach (
            $sorts as $index => $sort
        ) {
            if (!is_array($sort)) {
                continue;
            }

            $field =
                $sort['field'] ?? null;

            /*
             * Validate field.
             */
            if (
                !is_string($field)
                || !in_array(
                    $field,
                    self::SORT_FIELDS,
                    true
                )
            ) {
                $validator->errors()->add(
                    "sorts.{$index}.field",
                    'Unsupported sort field.'
                );

                continue;
            }

            /*
             * Duplicate field.
             */
            if (
                in_array(
                    $field,
                    $fields,
                    true
                )
            ) {
                $validator->errors()->add(
                    "sorts.{$index}.field",
                    'The same sort field cannot be used more than once.'
                );
            }

            $fields[] = $field;
        }
    }
}