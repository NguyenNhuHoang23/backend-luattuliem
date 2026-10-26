<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'phone' => $this->phone,
            'phoneHref' => $this->phoneHref(),
            'email' => $this->email,
            'address' => $this->address,
            'workingHours' => $this->working_hours,
            'website' => $this->website,
            'slogan' => $this->slogan,
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
