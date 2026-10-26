<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\HomePageResource;
use App\Models\HomePage;
use Illuminate\Http\JsonResponse;

class PublicHomePageController extends Controller
{
    public function show(): JsonResponse
    {
        return (new HomePageResource(HomePage::current()))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }
}
