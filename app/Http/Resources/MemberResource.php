<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'role' => $this->role,
            'group' => $this->role,
            'phone' => $this->phone,
            'email' => $this->email,
            'area' => $this->area,
            'status' => $this->status,
            'joinDate' => $this->join_date,
            'cases' => $this->when(
                $this->relationLoaded('caseConversations'),
                fn () => $this->caseConversations
                    ->map(fn (Conversation $conversation) => [
                        'id' => $conversation->uuid,
                        'uuid' => $conversation->uuid,
                        'name' => $conversation->name ?: 'Vụ việc',
                        'description' => $conversation->description,
                        'type' => 'cases',
                    ])
                    ->values(),
            ),
        ];
    }
}
