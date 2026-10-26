<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNewsArticleRequest;
use App\Http\Resources\NewsArticleResource;
use App\Models\NewsArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsArticleController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = NewsArticle::query()
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->get();

        return NewsArticleResource::collection($articles)
            ->additional(['success' => true])
            ->response();
    }

    public function store(StoreNewsArticleRequest $request): JsonResponse
    {
        $article = NewsArticle::query()->create(
            $this->articleAttributes($request)
        );

        return (new NewsArticleResource($article))
            ->additional([
                'success' => true,
                'message' => 'Đã tạo tin tức.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreNewsArticleRequest $request, NewsArticle $newsArticle): JsonResponse
    {
        $newsArticle->update(
            $this->articleAttributes($request, $newsArticle)
        );

        return (new NewsArticleResource($newsArticle->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật tin tức.',
            ])
            ->response();
    }

    public function destroy(NewsArticle $newsArticle): JsonResponse
    {
        $this->deleteStoredImage($newsArticle->image);
        $newsArticle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tin tức.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function articleAttributes(StoreNewsArticleRequest $request, ?NewsArticle $existing = null): array
    {
        $data = $request->validated();
        $content = $data['content'];

        $attributes = [
            'title' => $data['title'],
            'published_on' => $data['date'],
            'content' => $content,
            'excerpt' => filled($data['excerpt'] ?? null)
                ? $data['excerpt']
                : $this->excerptFromContent($content),
            'published' => $data['published'] ?? $existing?->published ?? true,
            'image' => $this->storeImage($request, $existing) ?? $existing?->image,
        ];

        if (! $existing || filled($data['slug'] ?? null)) {
            $attributes['slug'] = NewsArticle::uniqueSlug(
                $data['title'],
                $data['slug'] ?? null,
                $existing?->id,
            );
        }

        return $attributes;
    }

    private function storeImage(StoreNewsArticleRequest $request, ?NewsArticle $existing = null): ?string
    {
        if (! $request->hasFile('image')) {
            return $existing?->image;
        }

        $path = $request->file('image')->store('news', 'public');

        if ($existing?->image && $existing->image !== $path) {
            $this->deleteStoredImage($existing->image);
        }

        return $path;
    }

    private function deleteStoredImage(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'news/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function excerptFromContent(string $content): string
    {
        $text = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return Str::limit(trim($text), 180);
    }
}
