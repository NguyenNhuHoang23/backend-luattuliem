<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentRoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'key' => $this->id,
            'name' => $this->name,
            'title' => $this->title ?: $this->name,
            'applyLabel' => $this->apply_label ?: 'Gửi hồ sơ ứng tuyển',
            'audience' => $this->audience ?? [],
            'audienceNote' => $this->audience_note ?? [],
            'work' => $this->work ?? [],
            'workNote' => $this->work_note ?? [],
            'income' => $this->income ?? [],
            'sections' => $this->sections(),
            'sortOrder' => $this->sort_order,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
