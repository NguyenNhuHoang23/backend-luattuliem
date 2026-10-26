<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberCaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('memberIds') && ! $this->exists('member_ids')) {
            $this->merge([
                'member_ids' => $this->input('memberIds'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('members', 'id')->where(function ($query) {
                    $query->where('role', '!=', 'director')
                        ->where('status', 'active');
                }),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên vụ việc.',
            'member_ids.required' => 'Vui lòng chọn ít nhất một thành viên.',
            'member_ids.min' => 'Vui lòng chọn ít nhất một thành viên.',
            'member_ids.*.exists' => 'Thành viên không tồn tại hoặc đang tạm khóa.',
            'member_ids.*.distinct' => 'Danh sách thành viên bị trùng.',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function memberIds(): array
    {
        return collect($this->validated('member_ids'))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
