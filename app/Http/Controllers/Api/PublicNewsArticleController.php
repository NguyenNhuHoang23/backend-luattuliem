<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NewsArticleResource;
use App\Models\NewsArticle;
use Illuminate\Http\JsonResponse;

class PublicNewsArticleController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = NewsArticle::query()
            ->published()
            ->orderByDesc('published_on')
            ->orderByDesc('id')
            ->get();

        return NewsArticleResource::collection($articles)
            ->additional(['success' => true])
            ->response();
    }

    public function show(string $newsArticle): JsonResponse
    {
        $article = NewsArticle::query()
            ->published()
            ->where(function ($query) use ($newsArticle) {
                $query->where('slug', $newsArticle)->orWhere('id', $newsArticle);
            })
            ->first();

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy tin tức.',
            ], 404);
        }

        return (new NewsArticleResource($article))
            ->additional(['success' => true])
            ->response();
    }
}
