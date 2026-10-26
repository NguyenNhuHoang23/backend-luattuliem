<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RecruitmentPageResource;
use App\Models\RecruitmentPage;
use Illuminate\Http\JsonResponse;

class PublicRecruitmentPageController extends Controller
{
    public function show(): JsonResponse
    {
        $page = RecruitmentPage::query()->first() ?? new RecruitmentPage([
            'title' => RecruitmentPage::DEFAULT_TITLE,
            'intro' => RecruitmentPage::DEFAULT_INTRO,
        ]);

        return (new RecruitmentPageResource($page))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }
}
