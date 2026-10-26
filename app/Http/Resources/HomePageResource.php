<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomePageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $content = $this->payload();
        $content['slides'] = array_map(function ($slide) use ($request) {
            return [
                'caption' => $slide['caption'] ?? '',
                'path' => $slide['path'] ?? null,
                'image' => $this->slideImageUrl($request, $slide['path'] ?? null),
            ];
        }, $content['slides'] ?? []);

        return $content + [
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function slideImageUrl(Request $request, ?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            if (preg_match('#^https?://(?:localhost|127\\.0\\.0\\.1)(?::\\d+)?(/api/.+)$#i', $path, $matches)) {
                return $request->getSchemeAndHttpHost().$matches[1];
            }

            return $path;
        }

        if (str_starts_with($path, 'home/')) {
            return $request->getSchemeAndHttpHost().'/api/trang-chu-anh/'.rawurlencode(basename($path));
        }

        return $path;
    }
}
