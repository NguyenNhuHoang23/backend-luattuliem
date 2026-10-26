<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompanySettingRequest;
use App\Http\Resources\CompanySettingResource;
use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;

class CompanySettingController extends Controller
{
    public function show(): JsonResponse
    {
        return (new CompanySettingResource(CompanySetting::current()))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }

    public function update(StoreCompanySettingRequest $request): JsonResponse
    {
        $setting = CompanySetting::current();
        $setting->update($request->settings());

        return (new CompanySettingResource($setting->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật thông tin công ty.',
            ])
            ->response()
            ->setStatusCode(200);
    }
}
