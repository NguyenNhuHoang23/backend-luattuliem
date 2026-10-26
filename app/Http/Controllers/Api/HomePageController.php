<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHomePageRequest;
use App\Http\Resources\HomePageResource;
use App\Models\HomePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class HomePageController extends Controller
{
    public function show(): JsonResponse
    {
        return (new HomePageResource(HomePage::current()))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }

    public function update(StoreHomePageRequest $request): JsonResponse
    {
        $page = HomePage::current();
        $incoming = $request->validated();
        $content = $page->payload();

        foreach (['hero', 'intro', 'fields', 'cooperate', 'articles', 'contact'] as $section) {
            if (isset($incoming[$section]) && is_array($incoming[$section])) {
                $content[$section] = array_replace_recursive(
                    $content[$section] ?? [],
                    $incoming[$section],
                );
            }
        }

        if (isset($incoming['principles'])) {
            $content['principles'] = array_values($incoming['principles']);
        }
        if (isset($incoming['fields']['items'])) {
            $content['fields']['items'] = array_values($incoming['fields']['items']);
        }
        if (isset($incoming['cooperate']['roles'])) {
            $content['cooperate']['roles'] = array_values($incoming['cooperate']['roles']);
        }
        if (isset($incoming['articles']['items'])) {
            $content['articles']['items'] = array_values($incoming['articles']['items']);
        }

        $content['slides'] = $this->syncSlides(
            $request,
            $incoming['slides'] ?? $content['slides'] ?? [],
            $content['slides'] ?? [],
        );

        $page->update(['content' => $content]);

        return (new HomePageResource($page->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật trang chủ.',
            ])
            ->response()
            ->setStatusCode(200);
    }

    /**
     * @param  array<int, array<string, mixed>>  $incoming
     * @param  array<int, array<string, mixed>>  $existing
     * @return array<int, array{caption: string, path: ?string}>
     */
    private function syncSlides(StoreHomePageRequest $request, array $incoming, array $existing): array
    {
        $keptPaths = [];
        $slides = [];

        foreach (array_values($incoming) as $index => $slide) {
            $caption = trim((string) ($slide['caption'] ?? ''));
            $path = $slide['path'] ?? ($existing[$index]['path'] ?? null);
            $file = $request->file('slides.'.$index.'.image');
            if (! $file instanceof UploadedFile) {
                $files = $request->file('slides');
                $file = is_array($files) ? ($files[$index]['image'] ?? null) : null;
            }

            if ($file instanceof UploadedFile) {
                $stored = $file->store('home', 'public');
                if (is_string($path) && str_starts_with($path, 'home/') && $path !== $stored) {
                    Storage::disk('public')->delete($path);
                }
                $path = $stored;
            }

            if (! $caption && ! $path) {
                continue;
            }

            if (is_string($path) && str_starts_with($path, 'home/')) {
                $keptPaths[] = $path;
            }

            $slides[] = [
                'caption' => $caption,
                'path' => $path ?: null,
            ];
        }

        foreach ($existing as $old) {
            $oldPath = $old['path'] ?? null;
            if (is_string($oldPath) && str_starts_with($oldPath, 'home/') && ! in_array($oldPath, $keptPaths, true)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        return $slides;
    }
}
