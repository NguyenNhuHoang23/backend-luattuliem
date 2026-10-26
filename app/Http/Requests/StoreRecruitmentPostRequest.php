<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecruitmentPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->exists('publicationOrder') && ! $this->exists('publication_order')) {
            $merge['publication_order'] = $this->input('publicationOrder');
        }

        $paragraphs = $merge['paragraphs'] ?? $this->input('paragraphs');
        if (is_string($paragraphs)) {
            $merge['paragraphs'] = $this->lines($paragraphs);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('recruitment_posts', 'slug')->ignore($this->route('recruitmentPost')),
            ],
            'description' => ['required', 'string'],
            'paragraphs' => ['nullable', 'array'],
            'paragraphs.*' => ['string'],
            'role' => ['nullable', 'string', 'max:255'],
            'publication_order' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề tin tuyển dụng.',
            'description.required' => 'Vui lòng nhập mô tả ngắn.',
        ];
    }

    /**
     * @return list<string>
     */
    private function lines(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value) ?: [])));
    }
}
