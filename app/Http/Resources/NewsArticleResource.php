<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsArticleResource extends JsonResource
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
            'date' => $this->published_on?->format('d-m-Y'),
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'image' => $this->imageUrl($request),
            'published' => (bool) $this->published,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function imageUrl(Request $request): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            if (preg_match('#^https?://(?:localhost|127\\.0\\.0\\.1)(?::\\d+)?(/api/.+)$#i', $this->image, $matches)) {
                return $request->getSchemeAndHttpHost().$matches[1];
            }

            return $this->image;
        }

        if (str_starts_with($this->image, 'news/')) {
            return $request->getSchemeAndHttpHost().'/api/tin-tuc-anh/'.rawurlencode(basename($this->image));
        }

        return $this->image;
    }
}
