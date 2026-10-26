<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $files = collect($this->files ?? [])
            ->values()
            ->map(fn ($file, $index) => [
                'index' => $index,
                'name' => is_array($file) ? ($file['name'] ?? '') : (string) $file,
            ])
            ->all();

        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'content' => $this->content,
            'files' => $files,
            'status' => $this->status,
            'createdAt' => $this->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d-m-Y H:i'),
        ];
    }
}
