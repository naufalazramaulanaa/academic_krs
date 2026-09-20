<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan untuk membuat permintaan ini.
     */
    public function authorize(): bool
    {
        return true; // Wajib diset true agar request tidak ditolak (403 Forbidden)
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk permintaan tersebut.
     */
    public function rules(): array
    {
        return [
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
