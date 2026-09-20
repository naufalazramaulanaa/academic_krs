<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student.nim' => [
                'required',
                'string',
                'regex:/^[0-9]{8,12}$/',
            ],

            'student.name' => [
                'required',
                'string',
                'min:3',
                'max:100',
            ],

            'student.email' => [
                'required',
                'email',
                'max:150',
            ],

            'course.code' => [
                'required',
                'string',
                'regex:/^[A-Z]{2,4}[0-9]{3}$/',
            ],

            'course.name' => [
                'required',
                'string',
                'min:3',
                'max:120',
            ],

            'course.credits' => [
                'required',
                'integer',
                'between:1,6',
            ],

            'academic_year' => [
                'required',
                'regex:/^[0-9]{4}\/[0-9]{4}$/',
            ],

            'semester' => [
                'required',
                'in:GANJIL,GENAP',
            ],

            'status' => [
                'required',
                'in:DRAFT,SUBMITTED,APPROVED,REJECTED',
            ],
        ];
    }
}