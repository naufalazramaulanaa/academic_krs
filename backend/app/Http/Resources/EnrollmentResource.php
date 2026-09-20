<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'academic_year' => $this->academic_year,
            'semester' => $this->semester,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Memuat data student secara kondisional jika di-load di controller
            'student' => [
                'id' => $this->whenLoaded('student', function() {
                    return $this->student->id;
                }),
                'nim' => $this->whenLoaded('student', function() {
                    return $this->student->nim;
                }),
                'name' => $this->whenLoaded('student', function() {
                    return $this->student->name;
                }),
                'email' => $this->whenLoaded('student', function() {
                    return $this->student->email;
                }),
            ],

            // Memuat data course secara kondisional jika di-load di controller
            'course' => [
                'id' => $this->whenLoaded('course', function() {
                    return $this->course->id;
                }),
                'code' => $this->whenLoaded('course', function() {
                    return $this->course->code;
                }),
                'name' => $this->whenLoaded('course', function() {
                    return $this->course->name;
                }),
                'credits' => $this->whenLoaded('course', function() {
                    return $this->course->credits;
                }),
            ],
        ];
    }
}
