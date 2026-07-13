<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
               'full_name'          => $this->full_name,
                'email'             => $this->email,
                'Avatar'            => $this->avatar,
                'Birth date'        => $this->profile?->birth_date ?? null,
                'Gender'            => $this->profile?->gender,
                'Age'               => $this->age,
                'is_smoker'         => $this->profile?->is_smoker ?? false,
                'has_diabetes'      => $this->profile?->has_diabetes ?? false,
                'has_hypertension'  => $this->profile?->has_hypertension ?? false,
                'is_pregnant'       => $this->profile?->is_pregnant ?? false,
                'activity_level'    => $this->profile?->activity_level ?? null,
                'last_checkup_date' => $this->profile?->last_checkup_date ?? null,
                
                ];

    }
}
