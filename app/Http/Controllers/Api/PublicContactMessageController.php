<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactMessageRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class PublicContactMessageController extends Controller
{
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $data = $request->validated();
        $files = [];
        $uploads = $request->file('files', []);
        if ($uploads instanceof UploadedFile) {
            $uploads = [$uploads];
        }

        foreach ($uploads as $file) {
            $files[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $file->store('contacts', 'public'),
            ];
        }

        $message = ContactMessage::query()->create([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'content' => $data['content'],
            'files' => $files,
            'status' => 'moi',
        ]);

        return (new ContactMessageResource($message))
            ->additional([
                'success' => true,
                'message' => 'Đã gửi yêu cầu liên hệ.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}
