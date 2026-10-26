<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanySettingResource;
use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;

class PublicCompanySettingController extends Controller
{
    public function show(): JsonResponse
    {
        return (new CompanySettingResource(CompanySetting::current()))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }
}
