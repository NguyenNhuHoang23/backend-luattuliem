<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversationId' => $this->conversation_id,
            'conversationUuid' => $this->when(
                $this->relationLoaded('conversation') && $this->conversation,
                fn () => $this->conversation->uuid,
            ),
            'authorId' => $this->member_id,
            'authorName' => $this->member?->name ?: 'Thành viên',
            'content' => $this->content,
            'createdAt' => $this->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d-m-Y H:i'),
            'time' => $this->created_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i d/m'),
            'attachments' => ChatAttachmentResource::collection($this->whenLoaded('attachments')),
        ];
    }
}
