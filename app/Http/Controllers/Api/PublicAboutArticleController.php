<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AboutArticleResource;
use App\Models\AboutArticle;
use Illuminate\Http\JsonResponse;

class PublicAboutArticleController extends Controller
{
    public function index(): JsonResponse
    {
        $articles = AboutArticle::query()
            ->published()
            ->orderBy('publication_order')
            ->orderBy('id')
            ->get();

        return AboutArticleResource::collection($articles)
            ->additional(['success' => true])
            ->response();
    }

    public function show(string $aboutArticle): JsonResponse
    {
        $article = AboutArticle::query()
            ->published()
            ->where(function ($query) use ($aboutArticle) {
                $query->where('slug', $aboutArticle)->orWhere('id', $aboutArticle);
            })
            ->first();

        if (! $article) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài giới thiệu.',
            ], 404);
        }

        return (new AboutArticleResource($article))
            ->additional(['success' => true])
            ->response();
    }
}
