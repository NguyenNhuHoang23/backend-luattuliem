<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecruitmentRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        $aliases = [
            'applyLabel' => 'apply_label',
            'audienceNote' => 'audience_note',
            'workNote' => 'work_note',
            'sortOrder' => 'sort_order',
        ];

        foreach ($aliases as $from => $to) {
            if ($this->exists($from) && ! $this->exists($to)) {
                $merge[$to] = $this->input($from);
            }
        }

        foreach (['audience', 'audience_note', 'work', 'work_note', 'income'] as $field) {
            $value = $merge[$field] ?? $this->input($field);
            if (is_string($value)) {
                $merge[$field] = $this->lines($value);
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
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('recruitment_roles', 'slug')->ignore($this->route('recruitmentRole')),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'apply_label' => ['nullable', 'string', 'max:255'],
            'audience' => ['nullable', 'array'],
            'audience.*' => ['string'],
            'audience_note' => ['nullable', 'array'],
            'audience_note.*' => ['string'],
            'work' => ['nullable', 'array'],
            'work.*' => ['string'],
            'work_note' => ['nullable', 'array'],
            'work_note.*' => ['string'],
            'income' => ['nullable', 'array'],
            'income.*' => ['string'],
            'sort_order' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên vị trí tuyển dụng.',
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
