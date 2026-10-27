<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->full_name,
            'fullName' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'role' => $this->role,
            'area' => $this->area,
            'request' => $this->experience,
            'experience' => $this->experience,
            'cvFileName' => $this->cv_file_name,
            'hasCv' => filled($this->cv_path),
            'status' => $this->reviewStatus(),
            'reviewNote' => $this->review_note,
            'reviewedAt' => $this->reviewed_at?->timezone('Asia/Ho_Chi_Minh')->format('d-m-Y H:i'),
            'createdAt' => $this->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d-m-Y H:i'),
            'memberId' => $this->member_id,
            'username' => $this->member?->username,
        ];
    }
}
