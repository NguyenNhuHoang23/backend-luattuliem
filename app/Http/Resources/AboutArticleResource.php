<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AboutArticleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->slug,
            'key' => $this->id,
            'title' => $this->title,
            'showTitle' => $this->show_title,
            'publicationOrder' => $this->publication_order,
            'status' => (bool) $this->status,
            'content' => $this->content,
            'blocks' => $this->blocks ?? [],
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
