<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAboutArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->exists('showTitle') && ! $this->exists('show_title')) {
            $merge['show_title'] = $this->boolean('showTitle');
        }

        if ($this->exists('publicationOrder') && ! $this->exists('publication_order')) {
            $merge['publication_order'] = $this->input('publicationOrder');
        }

        if ($this->exists('status')) {
            $merge['status'] = $this->boolean('status');
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
                Rule::unique('about_articles', 'slug')->ignore($this->route('aboutArticle')),
            ],
            'show_title' => ['sometimes', 'boolean'],
            'publication_order' => ['sometimes', 'integer', 'min:1'],
            'status' => ['sometimes', 'boolean'],
            'content' => ['required_without:blocks', 'nullable', 'string'],
            'blocks' => ['required_without:content', 'nullable', 'array'],
            'blocks.*.type' => ['required_with:blocks', 'string', 'in:paragraph,heading,cta,quote'],
            'blocks.*.text' => ['nullable', 'string'],
            'blocks.*.link' => ['nullable', 'string', 'max:255'],
            'blocks.*.strong' => ['nullable', 'boolean'],
            'blocks.*.parts' => ['nullable', 'array'],
            'blocks.*.parts.*.text' => ['nullable', 'string'],
            'blocks.*.parts.*.strong' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề bài giới thiệu.',
            'content.required_without' => 'Vui lòng nhập nội dung hoặc danh sách khối nội dung.',
            'blocks.required_without' => 'Vui lòng nhập nội dung hoặc danh sách khối nội dung.',
        ];
    }
}
