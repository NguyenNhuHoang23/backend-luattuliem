<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateContactMessageStatusRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactMessageController extends Controller
{
    public function index(): JsonResponse
    {
        $messages = ContactMessage::query()
            ->orderByDesc('id')
            ->get();

        return ContactMessageResource::collection($messages)
            ->additional(['success' => true])
            ->response();
    }

    public function show(ContactMessage $contactMessage): JsonResponse
    {
        return (new ContactMessageResource($contactMessage))
            ->additional(['success' => true])
            ->response();
    }

    public function file(ContactMessage $contactMessage, int $index): StreamedResponse
    {
        $file = ($contactMessage->files ?? [])[$index] ?? null;
        $path = is_array($file) ? ($file['path'] ?? null) : null;

        abort_unless(
            $path && str_starts_with($path, 'contacts/') && Storage::disk('public')->exists($path),
            404,
            'Không tìm thấy tệp đính kèm.',
        );

        $filename = str_replace(['"', '\\', "\r", "\n"], '', $file['name'] ?? basename($path)) ?: 'tep-dinh-kem';

        return Storage::disk('public')->response($path, $filename, [], 'inline');
    }

    public function updateStatus(
        UpdateContactMessageStatusRequest $request,
        ContactMessage $contactMessage,
    ): JsonResponse {
        $contactMessage->update($request->validated());

        return (new ContactMessageResource($contactMessage->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái liên hệ.',
            ])
            ->response();
    }

    public function destroy(ContactMessage $contactMessage): JsonResponse
    {
        foreach ($contactMessage->files ?? [] as $file) {
            $path = is_array($file) ? ($file['path'] ?? null) : null;
            if ($path && str_starts_with($path, 'contacts/')) {
                Storage::disk('public')->delete($path);
            }
        }

        $contactMessage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa liên hệ.',
        ]);
    }
}
