<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('content'))) {
            $decoded = json_decode($this->input('content'), true);
            if (is_array($decoded)) {
                $this->merge($decoded);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'hero.title' => ['required', 'string', 'max:255'],
            'hero.subtitle' => ['nullable', 'string', 'max:1000'],
            'hero.primaryButton.label' => ['required', 'string', 'max:120'],
            'hero.primaryButton.href' => ['required', 'string', 'max:255'],
            'hero.secondaryButton.label' => ['nullable', 'string', 'max:120'],
            'hero.secondaryButton.href' => ['nullable', 'string', 'max:255'],
            'slides' => ['nullable', 'array', 'max:8'],
            'slides.*.caption' => ['nullable', 'string', 'max:160'],
            'slides.*.path' => ['nullable', 'string', 'max:255'],
            'slides.*.image' => ['nullable', 'file', 'image', 'max:5120'],
            'principles' => ['nullable', 'array', 'max:6'],
            'principles.*.number' => ['nullable', 'string', 'max:10'],
            'principles.*.title' => ['required_with:principles', 'string', 'max:160'],
            'principles.*.text' => ['nullable', 'string', 'max:255'],
            'intro.eyebrow' => ['nullable', 'string', 'max:80'],
            'intro.title' => ['required', 'string', 'max:255'],
            'intro.body' => ['nullable', 'string', 'max:4000'],
            'intro.quote' => ['nullable', 'string', 'max:500'],
            'fields.eyebrow' => ['nullable', 'string', 'max:80'],
            'fields.title' => ['required', 'string', 'max:255'],
            'fields.description' => ['nullable', 'string', 'max:1000'],
            'fields.items' => ['nullable', 'array', 'max:9'],
            'fields.items.*.title' => ['required_with:fields.items', 'string', 'max:160'],
            'fields.items.*.description' => ['nullable', 'string', 'max:500'],
            'fields.items.*.buttonLabel' => ['nullable', 'string', 'max:80'],
            'fields.items.*.buttonHref' => ['nullable', 'string', 'max:255'],
            'cooperate.eyebrow' => ['nullable', 'string', 'max:80'],
            'cooperate.title' => ['required', 'string', 'max:255'],
            'cooperate.body' => ['nullable', 'string', 'max:4000'],
            'cooperate.notice' => ['nullable', 'string', 'max:1000'],
            'cooperate.hint' => ['nullable', 'string', 'max:255'],
            'cooperate.roles' => ['nullable', 'array', 'max:9'],
            'cooperate.roles.*.name' => ['required_with:cooperate.roles', 'string', 'max:160'],
            'cooperate.roles.*.description' => ['nullable', 'string', 'max:500'],
            'cooperate.roles.*.buttonLabel' => ['nullable', 'string', 'max:120'],
            'articles.eyebrow' => ['nullable', 'string', 'max:80'],
            'articles.title' => ['required', 'string', 'max:255'],
            'articles.items' => ['nullable', 'array', 'max:9'],
            'articles.items.*.summary' => ['required_with:articles.items', 'string', 'max:255'],
            'articles.items.*.body' => ['nullable', 'string', 'max:2000'],
            'contact.eyebrow' => ['nullable', 'string', 'max:80'],
            'contact.title' => ['required', 'string', 'max:255'],
            'contact.body' => ['nullable', 'string', 'max:1000'],
            'contact.buttonLabel' => ['nullable', 'string', 'max:80'],
            'contact.notice' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hero.title.required' => 'Vui lòng nhập tiêu đề banner.',
            'hero.primaryButton.label.required' => 'Vui lòng nhập nhãn nút chính.',
            'hero.primaryButton.href.required' => 'Vui lòng nhập liên kết nút chính.',
            'intro.title.required' => 'Vui lòng nhập tiêu đề phần giới thiệu.',
            'fields.title.required' => 'Vui lòng nhập tiêu đề lĩnh vực.',
            'cooperate.title.required' => 'Vui lòng nhập tiêu đề tuyển dụng.',
            'articles.title.required' => 'Vui lòng nhập tiêu đề bài viết.',
            'contact.title.required' => 'Vui lòng nhập tiêu đề phần liên hệ.',
            'slides.*.image.image' => 'Ảnh slider phải là định dạng ảnh.',
            'slides.*.image.max' => 'Mỗi ảnh slider tối đa 5 MB.',
        ];
    }
}
