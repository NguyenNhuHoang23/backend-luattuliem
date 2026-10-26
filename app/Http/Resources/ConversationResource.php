<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $actor = $request->attributes->get('member');
        $peer = $actor instanceof Member ? $this->peerFor($actor) : null;
        $latest = $this->relationLoaded('latestMessage')
            ? $this->latestMessage
            : $this->messages->last();

        $isDirect = $this->type === Conversation::TYPE_DIRECT;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'name' => $this->name,
            'peer' => $isDirect && $peer ? [
                'id' => $peer->id,
                'name' => $peer->name,
                'username' => $peer->username,
                'role' => $peer->role,
            ] : null,
            'members' => $this->when(
                $this->relationLoaded('members'),
                fn () => $this->members->map(fn (Member $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'username' => $member->username,
                    'role' => $member->role,
                ])->values(),
            ),
            'lastMessage' => $latest ? [
                'id' => $latest->id,
                'content' => $latest->content,
                'createdAt' => $latest->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d-m-Y H:i'),
            ] : null,
            'unread' => $actor instanceof Member ? $this->isUnreadFor($actor) : false,
            'hasMore' => (bool) $this->hasMoreMessages,
            'messages' => ChatMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
