<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecruitmentPostRequest;
use App\Http\Resources\RecruitmentPostResource;
use App\Models\RecruitmentPost;
use Illuminate\Http\JsonResponse;

class RecruitmentPostController extends Controller
{
    public function index(): JsonResponse
    {
        $posts = RecruitmentPost::query()
            ->orderByDesc('publication_order')
            ->orderByDesc('id')
            ->get();

        return RecruitmentPostResource::collection($posts)
            ->additional(['success' => true])
            ->response();
    }

    public function store(StoreRecruitmentPostRequest $request): JsonResponse
    {
        $post = RecruitmentPost::query()->create(
            $this->postAttributes($request->validated())
        );

        return (new RecruitmentPostResource($post))
            ->additional([
                'success' => true,
                'message' => 'Đã tạo tin tuyển dụng.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreRecruitmentPostRequest $request, RecruitmentPost $recruitmentPost): JsonResponse
    {
        $recruitmentPost->update(
            $this->postAttributes($request->validated(), $recruitmentPost)
        );

        return (new RecruitmentPostResource($recruitmentPost->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật tin tuyển dụng.',
            ])
            ->response();
    }

    public function destroy(RecruitmentPost $recruitmentPost): JsonResponse
    {
        $recruitmentPost->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa tin tuyển dụng.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function postAttributes(array $data, ?RecruitmentPost $existing = null): array
    {
        $title = $data['title'];
        $attributes = [
            'title' => $title,
            'description' => $data['description'],
            'paragraphs' => $data['paragraphs'] ?? $existing?->paragraphs ?? [],
            'role' => $data['role'] ?? $existing?->role,
            'publication_order' => $data['publication_order']
                ?? $existing?->publication_order
                ?? RecruitmentPost::nextOrder(),
        ];

        if (! $existing || filled($data['slug'] ?? null)) {
            $attributes['slug'] = RecruitmentPost::uniqueSlug(
                $title,
                $data['slug'] ?? null,
                $existing?->id,
            );
        }

        return $attributes;
    }
}
