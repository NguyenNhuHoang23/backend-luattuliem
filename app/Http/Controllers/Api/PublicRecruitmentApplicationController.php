<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecruitmentApplicationRequest;
use App\Http\Resources\RecruitmentApplicationResource;
use App\Models\RecruitmentApplication;
use Illuminate\Http\JsonResponse;

class PublicRecruitmentApplicationController extends Controller
{
    public function store(StoreRecruitmentApplicationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $file = $request->file('cv');

        $application = RecruitmentApplication::query()->create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'role' => $data['role'],
            'area' => $data['area'],
            'experience' => $data['experience'],
            'cv_file_name' => $file->getClientOriginalName(),
            'cv_path' => $file->store('applications', 'public'),
            'status' => 'moi',
        ]);

        return (new RecruitmentApplicationResource($application))
            ->additional([
                'success' => true,
                'message' => 'Đã gửi hồ sơ ứng tuyển.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}
