<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CaseArticleResource;
use App\Models\CaseArticle;
use Illuminate\Http\JsonResponse;

class PublicCaseArticleController extends Controller
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

    public function show(string $caseArticle): JsonResponse
    {
        $article = CaseArticle::query()
            ->where(function ($query) use ($caseArticle) {
                $query->where('slug', $caseArticle)->orWhere('id', $caseArticle);
            })
            ->first();

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy án lệ / án hay.',
            ], 404);
        }

        return (new CaseArticleResource($article))
            ->additional(['success' => true])
            ->response();
    }
}
