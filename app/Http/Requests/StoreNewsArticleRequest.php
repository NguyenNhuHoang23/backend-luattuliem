<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class StoreNewsArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->exists('published')) {
            $merge['published'] = $this->boolean('published');
        }

        if ($this->filled('date')) {
            $parsed = $this->parseDate((string) $this->input('date'));
            if ($parsed) {
                $merge['date'] = $parsed->toDateString();
            }
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
        $isUpdate = (bool) $this->route('newsArticle');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('news_articles', 'slug')->ignore($this->route('newsArticle')),
            ],
            'date' => ['required', 'date'],
            'content' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $text = trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    if ($text === '') {
                        $fail('Vui lòng nhập nội dung tin tức.');
                    }
                },
            ],
            'excerpt' => ['nullable', 'string'],
            'image' => [
                Rule::requiredIf(! $isUpdate),
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp,gif',
                'max:4096',
            ],
            'published' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề tin tức.',
            'date.required' => 'Vui lòng nhập ngày đăng.',
            'date.date' => 'Ngày đăng không hợp lệ.',
            'content.required' => 'Vui lòng nhập nội dung tin tức.',
            'image.required' => 'Vui lòng tải ảnh đại diện cho tin tức.',
            'image.image' => 'Ảnh đại diện phải là tệp hình ảnh.',
            'image.mimes' => 'Ảnh đại diện chỉ nhận jpg, jpeg, png, webp hoặc gif.',
            'image.max' => 'Ảnh đại diện tối đa 4MB.',
        ];
    }

    private function parseDate(string $raw): ?Carbon
    {
        $value = trim($raw);
        $formats = [
            '/^\d{4}-\d{2}-\d{2}$/' => 'Y-m-d',
            '/^\d{2}-\d{2}-\d{4}$/' => 'd-m-Y',
            '/^\d{2}\/\d{2}\/\d{4}$/' => 'd/m/Y',
        ];

        foreach ($formats as $pattern => $format) {
            if (! preg_match($pattern, $value)) {
                continue;
            }

            try {
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
