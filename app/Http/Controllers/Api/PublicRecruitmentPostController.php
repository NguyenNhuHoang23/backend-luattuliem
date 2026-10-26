<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecruitmentPostResource;
use App\Models\RecruitmentPost;
use Illuminate\Http\JsonResponse;

class PublicRecruitmentPostController extends Controller
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

    public function show(string $recruitmentPost): JsonResponse
    {
        $post = RecruitmentPost::query()
            ->where(function ($query) use ($recruitmentPost) {
                $query->where('slug', $recruitmentPost)->orWhere('id', $recruitmentPost);
            })
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tin tuyển dụng.',
            ], 404);
        }

        return (new RecruitmentPostResource($post))
            ->additional(['success' => true])
            ->response();
    }
}
