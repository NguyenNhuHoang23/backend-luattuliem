<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaseArticleRequest;
use App\Http\Resources\CaseArticleResource;
use App\Models\CaseArticle;
use Illuminate\Http\JsonResponse;

class CaseArticleController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = CaseArticle::query()
            ->orderByDesc('featured')
            ->orderByDesc('publication_order')
            ->orderByDesc('id')
            ->get();

        return CaseArticleResource::collection($articles)
            ->additional(['success' => true])
            ->response();
    }

    public function store(StoreCaseArticleRequest $request): JsonResponse
    {
        $article = CaseArticle::query()->create(
            $this->articleAttributes($request->validated())
        );

        return (new CaseArticleResource($article))
            ->additional([
                'success' => true,
                'message' => 'Đã tạo án lệ / án hay.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreCaseArticleRequest $request, CaseArticle $caseArticle): JsonResponse
    {
        $caseArticle->update(
            $this->articleAttributes($request->validated(), $caseArticle)
        );

        return (new CaseArticleResource($caseArticle->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật án lệ / án hay.',
            ])
            ->response();
    }

    public function destroy(CaseArticle $caseArticle): JsonResponse
    {
        $caseArticle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa án lệ / án hay.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function articleAttributes(array $data, ?CaseArticle $existing = null): array
    {
        $title = $data['title'];
        $caseNumber = $data['case_number'];
        $court = $data['source_court'] ?? $existing?->source_court;
        $tag = $data['tag'] ?? $existing?->tag;
        $overview = $data['overview'] ?? $existing?->overview;
        $field = $data['field'] ?? $existing?->field;
        $status = $data['status'] ?? $existing?->status ?? 'Đang áp dụng';

        $attributes = [
            'title' => $title,
            'case_number' => $caseNumber,
            'code' => $data['code'] ?? $existing?->code ?? $caseNumber,
            'category' => $data['category'],
            'tag' => $tag,
            'description' => $data['description'],
            'overview' => $overview,
            'source_court' => $court,
            'published_on' => $data['published_on'] ?? $existing?->published_on,
            'featured' => $data['featured'] ?? $existing?->featured ?? false,
            'publication_order' => $data['publication_order']
                ?? $existing?->publication_order
                ?? CaseArticle::nextOrder(),
            'field' => $field,
            'status' => $status,
            'keywords' => $data['keywords'] ?? $existing?->keywords ?? [],
            'legal_provisions' => $data['legal_provisions'] ?? $existing?->legal_provisions ?? [],
            'case_body' => $data['case_body'] ?? $existing?->case_body ?? [],
            'related_docs' => $data['related_docs'] ?? $existing?->related_docs ?? [],
            'attributes' => [
                'agency' => $court,
                'field' => $field,
                'status' => $status,
            ],
            'modal' => [
                'title' => $title,
                'topic' => $tag,
                'court' => $court,
                'result' => $overview,
            ],
        ];

        if (! $existing || filled($data['slug'] ?? null)) {
            $attributes['slug'] = CaseArticle::uniqueSlug(
                $caseNumber ?: $title,
                $data['slug'] ?? null,
                $existing?->id,
            );
        }

        return $attributes;
    }
}
