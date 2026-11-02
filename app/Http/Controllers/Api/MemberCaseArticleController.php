<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCaseArticleRequest;
use App\Http\Resources\CaseArticleResource;
use App\Models\CaseArticle;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MemberCaseArticleController extends Controller
{
    public function store(StoreCaseArticleRequest $request): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        $article = CaseArticle::query()->create(
            $this->articlePayload($request)
        );

        return (new CaseArticleResource($article))
            ->additional([
                'success' => true,
                'message' => 'Đã đăng án lệ / án hay.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function update(StoreCaseArticleRequest $request, CaseArticle $caseArticle): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        $caseArticle->update(
            $this->articlePayload($request, $caseArticle)
        );

        return (new CaseArticleResource($caseArticle->refresh()))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật án lệ / án hay.',
            ])
            ->response();
    }

    public function storeImage(Request $request): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ], [
            'image.required' => 'Vui lòng chọn ảnh.',
            'image.image' => 'File phải là ảnh.',
            'image.mimes' => 'Ảnh chỉ nhận jpg, png, webp hoặc gif.',
            'image.max' => 'Ảnh không được vượt quá 4MB.',
        ]);

        $path = $request->file('image')->store('case-images', 'public');

        return response()->json([
            'success' => true,
            'data' => [
                'url' => $request->getSchemeAndHttpHost().'/api/an-le-anh/'.rawurlencode(basename($path)),
                'filename' => basename($path),
            ],
        ], 201);
    }

    public function destroy(Request $request, CaseArticle $caseArticle): JsonResponse
    {
        if ($forbidden = $this->directorOrForbidden($request)) {
            return $forbidden;
        }

        $this->deleteStoredPdf($caseArticle->details['pdfPath'] ?? null);
        $this->deleteStoredImage($caseArticle->details['imagePath'] ?? null);
        $this->deleteBodyImages($caseArticle->case_body ?? []);
        $caseArticle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa án lệ / án hay.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function articlePayload(StoreCaseArticleRequest $request, ?CaseArticle $existing = null): array
    {
        $payload = CaseArticle::payloadFrom($request->validated(), $existing);
        $payload['details'] = $this->mergeMediaDetails($request, $existing);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function mergeMediaDetails(StoreCaseArticleRequest $request, ?CaseArticle $existing): array
    {
        $details = is_array($existing?->details) ? $existing->details : [];

        if ($request->boolean('remove_pdf') && ! $request->hasFile('pdf')) {
            $this->deleteStoredPdf($details['pdfPath'] ?? null);
            unset($details['pdfPath'], $details['pdfUrl'], $details['pdfTitle']);
        }

        if ($request->hasFile('pdf')) {
            $this->deleteStoredPdf($details['pdfPath'] ?? null);
            $details['pdfPath'] = $request->file('pdf')->store('cases', 'public');
            unset($details['pdfUrl']);
            if (! $request->filled('pdf_title') && empty($details['pdfTitle'])) {
                $details['pdfTitle'] = $request->file('pdf')->getClientOriginalName();
            }
        }

        if ($request->filled('pdf_title')) {
            $details['pdfTitle'] = $request->string('pdf_title')->toString();
        }

        if ($request->boolean('remove_image') && ! $request->hasFile('image')) {
            $this->deleteStoredImage($details['imagePath'] ?? null);
            unset($details['imagePath'], $details['image'], $details['imageCaption']);
        }

        if ($request->hasFile('image')) {
            $this->deleteStoredImage($details['imagePath'] ?? null);
            $details['imagePath'] = $request->file('image')->store('case-images', 'public');
            unset($details['image']);
        }

        if ($request->filled('image_caption')) {
            $details['imageCaption'] = $request->string('image_caption')->toString();
        }

        return $details;
    }

    /**
     * @param  list<mixed>  $caseBody
     */
    private function deleteBodyImages(mixed $caseBody): void
    {
        $text = is_string($caseBody) ? $caseBody : json_encode($caseBody);
        if (! is_string($text) || ! preg_match_all('#/an-le-anh/([A-Za-z0-9._-]+)#', $text, $matches)) {
            return;
        }

        foreach (array_unique($matches[1]) as $filename) {
            $this->deleteStoredImage('case-images/'.$filename);
        }
    }

    private function deleteStoredPdf(mixed $path): void
    {
        if (! is_string($path) || ! str_starts_with($path, 'cases/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function deleteStoredImage(mixed $path): void
    {
        if (! is_string($path) || ! str_starts_with($path, 'case-images/')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function directorOrForbidden(Request $request): ?JsonResponse
    {
        $actor = $request->attributes->get('member');

        if (! $actor instanceof Member || $actor->role !== 'director') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ Giám đốc được quản lý án lệ / án hay.',
            ], 403);
        }

        return null;
    }
}
