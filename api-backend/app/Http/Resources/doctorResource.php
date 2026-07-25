<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class doctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'full_name'           => $this->full_name,
            'email'               => $this->email,
            'phone'               => $this->phone,
            'specialization'      => $this->specialization,
            'years_of_experience' => $this->years_of_experience,
            'clinic_phone'        => $this->clinic_phone ?? null,
            'clinic_address'      => $this->clinic_address ?? null,
            'license_number'      => $this->license_number ?? null,
            'biography'           => $this->biography ?? null,
            'photo'               => $this->photo ? asset('storage/' . $this->photo) : null,
            'cv_file'             => $this->cv_file ? asset('storage/' . $this->cv_file) : null,
            'license_file'        => $this->license_file ? asset('storage/' . $this->license_file) : null,
        ];
    }
}
