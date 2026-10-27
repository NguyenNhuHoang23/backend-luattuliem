<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'area' => $this->area,
            'request' => $this->request_note,
            'status' => $this->status,
            'reviewNote' => $this->review_note,
            'reviewedAt' => $this->reviewed_at?->timezone('Asia/Ho_Chi_Minh')->format('d-m-Y H:i'),
            'createdAt' => $this->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d-m-Y H:i'),
            'memberId' => $this->member_id,
            'username' => $this->member?->username,
        ];
    }
}
