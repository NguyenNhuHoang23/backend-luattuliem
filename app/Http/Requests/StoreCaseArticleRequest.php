<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class StoreCaseArticleRequest extends FormRequest
{
    public const CATEGORIES = ['dat-dai', 'hop-dong', 'thua-ke', 'to-tung', 'lao-dong'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        $aliases = [
            'caseNumber' => 'case_number',
            'sourceCourt' => 'source_court',
            'legalProvisions' => 'legal_provisions',
            'caseBody' => 'case_body',
            'relatedDocs' => 'related_docs',
            'publicationOrder' => 'publication_order',
            'updatedAt' => 'updated_at_input',
        ];

        foreach ($aliases as $from => $to) {
            if ($this->exists($from) && ! $this->exists($to)) {
                $merge[$to] = $this->input($from);
            }
        }

        if ($this->exists('featured')) {
            $merge['featured'] = $this->boolean('featured');
        }

        foreach (['keywords', 'legal_provisions'] as $field) {
            $value = $merge[$field] ?? $this->input($field);
            if (is_string($value)) {
                $merge[$field] = $this->lines($value);
            }
        }

        $related = $merge['related_docs'] ?? $this->input('related_docs');
        if (is_string($related)) {
            $merge['related_docs'] = $this->parseRelatedDocs($related);
        } elseif (is_array($related)) {
            $merge['related_docs'] = $this->normalizeRelatedDocs($related);
        }

        $dateRaw = $merge['updated_at_input'] ?? $this->input('updated_at_input') ?? $this->input('updatedAt');
        if (filled($dateRaw)) {
            $parsed = $this->parseDate((string) $dateRaw);
            if ($parsed) {
                $merge['published_on'] = $parsed->toDateString();
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('case_articles', 'slug')->ignore($this->route('caseArticle')),
            ],
            'case_number' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:120'],
            'category' => ['required', 'string', Rule::in(self::CATEGORIES)],
            'tag' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string'],
            'overview' => ['nullable', 'string'],
            'source_court' => ['nullable', 'string', 'max:255'],
            'published_on' => ['nullable', 'date'],
            'featured' => ['sometimes', 'boolean'],
            'publication_order' => ['sometimes', 'integer', 'min:1'],
            'field' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:120'],
            'keywords' => ['nullable', 'array'],
            'keywords.*' => ['string', 'max:120'],
            'legal_provisions' => ['nullable', 'array'],
            'legal_provisions.*' => ['string'],
            'case_body' => ['nullable', 'array'],
            'case_body.*.heading' => ['required_with:case_body', 'string', 'max:255'],
            'case_body.*.content' => ['nullable', 'string'],
            'related_docs' => ['nullable', 'array'],
            'related_docs.*.title' => ['required', 'string', 'max:255'],
            'related_docs.*.type' => ['nullable', 'string', 'max:80'],
            'related_docs.*.issuing_agency' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề án lệ / án hay.',
            'case_number.required' => 'Vui lòng nhập số án / mã.',
            'category.required' => 'Vui lòng chọn nhóm án lệ.',
            'category.in' => 'Nhóm án lệ không hợp lệ.',
            'description.required' => 'Vui lòng nhập mô tả ngắn.',
        ];
    }

    /**
     * @param  list<mixed>  $docs
     * @return list<array{title: string, type: string, issuing_agency: string}>
     */
    private function normalizeRelatedDocs(array $docs): array
    {
        return array_values(array_filter(array_map(function ($doc) {
            if (is_string($doc)) {
                return $this->parseRelatedDocLine($doc);
            }

            if (! is_array($doc)) {
                return null;
            }

            $title = trim((string) ($doc['title'] ?? ''));
            if ($title === '') {
                return null;
            }

            return [
                'title' => $title,
                'type' => trim((string) ($doc['type'] ?? '')),
                'issuing_agency' => trim((string) ($doc['issuing_agency'] ?? $doc['issuingAgency'] ?? '')),
            ];
        }, $docs)));
    }

    /**
     * @return list<array{title: string, type: string, issuing_agency: string}>
     */
    private function parseRelatedDocs(string $value): array
    {
        return $this->normalizeRelatedDocs($this->lines($value));
    }

    /**
     * @return array{title: string, type: string, issuing_agency: string}|null
     */
    private function parseRelatedDocLine(string $line): ?array
    {
        $parts = array_map('trim', explode('|', $line));
        $title = $parts[0] ?? '';
        if ($title === '') {
            return null;
        }

        return [
            'title' => $title,
            'type' => $parts[1] ?? '',
            'issuing_agency' => $parts[2] ?? '',
        ];
    }

    /**
     * @return list<string>
     */
    private function lines(string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $value) ?: [])));
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
