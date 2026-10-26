<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAboutArticleRequest;
use App\Http\Resources\AboutArticleResource;
use App\Models\AboutArticle;
use App\Support\AboutContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AboutArticleController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = AboutArticle::query()
            ->orderBy('publication_order')
            ->orderBy('id')
            ->get();

        return AboutArticleResource::collection($articles)
            ->additional(['success' => true])
            ->response();
    }

    public function store(StoreAboutArticleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $article = AboutArticle::query()->create(
            $this->articleAttributes($data)
        );
        $this->syncStatus($article, $data);

        return (new AboutArticleResource($article->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã tạo bài giới thiệu.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreAboutArticleRequest $request, AboutArticle $aboutArticle): JsonResponse
    {
        $data = $request->validated();
        $aboutArticle->update(
            $this->articleAttributes($data, $aboutArticle)
        );
        $this->syncStatus($aboutArticle, $data);

        return (new AboutArticleResource($aboutArticle->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật bài giới thiệu.',
            ])
            ->response();
    }

    public function updateStatus(Request $request, AboutArticle $aboutArticle): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'boolean'],
        ], [
            'status.required' => 'Vui lòng chọn trạng thái bài giới thiệu.',
        ]);

        $aboutArticle->setExclusiveStatus((bool) $data['status']);

        return (new AboutArticleResource($aboutArticle->refresh()))
            ->additional([
                'success' => true,
                'message' => $aboutArticle->status
                    ? 'Đã bật bài giới thiệu. Các bài khác đã được tắt.'
                    : 'Đã tắt bài giới thiệu.',
            ])
            ->response();
    }

    public function destroy(AboutArticle $aboutArticle): JsonResponse
    {
        $aboutArticle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa bài giới thiệu.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function articleAttributes(array $data, ?AboutArticle $existing = null): array
    {
        $blocks = $data['blocks'] ?? AboutContent::fromText($data['content'] ?? $existing?->content ?? '');
        $content = $data['content'] ?? AboutContent::toText($blocks);

        if ($blocks === [] && is_string($content) && trim($content) !== '') {
            $blocks = AboutContent::fromText($content);
        }

        if ($blocks === [] && trim((string) $content) === '') {
            throw ValidationException::withMessages([
                'content' => 'Vui lòng nhập nội dung bài giới thiệu.',
            ]);
        }

        $attributes = [
            'title' => $data['title'],
            'show_title' => $data['show_title'] ?? $existing?->show_title ?? true,
            'publication_order' => $data['publication_order']
                ?? $existing?->publication_order
                ?? AboutArticle::nextOrder(),
            'content' => $content,
            'blocks' => $blocks,
        ];

        if (! $existing) {
            $attributes['status'] = false;
        }

        if (! $existing || filled($data['slug'] ?? null)) {
            $attributes['slug'] = AboutArticle::uniqueSlug(
                $data['title'],
                $data['slug'] ?? null,
                $existing?->id,
            );
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncStatus(AboutArticle $article, array $data): void
    {
        if (! array_key_exists('status', $data)) {
            return;
        }

        $article->setExclusiveStatus((bool) $data['status']);
    }
}
