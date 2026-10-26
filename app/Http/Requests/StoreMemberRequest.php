<?php

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        $aliases = [
            'userName' => 'username',
            'joinDate' => 'join_date',
            'group' => 'role',
        ];

        foreach ($aliases as $from => $to) {
            if ($this->exists($from) && ! $this->exists($to)) {
                $merge[$to] = $this->input($from);
            }
        }

        $username = $merge['username'] ?? $this->input('username');
        if (is_string($username)) {
            $merge['username'] = strtolower(trim($username));
        }

        $role = $merge['role'] ?? $this->input('role');
        if (is_string($role)) {
            $merge['role'] = match (strtolower(trim($role))) {
                'giam-doc', 'giamdoc', 'director' => 'director',
                'ctv', 'collaborator' => 'ctv',
                default => strtolower(trim($role)),
            };
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
        $creating = $this->isMethod('POST');

        return [
            'name' => ['required', 'string', 'max:120'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('members', 'username')->ignore($this->route('member')),
            ],
            'password' => [$creating ? 'required' : 'nullable', 'string', 'min:6', 'max:72'],
            'role' => ['required', 'string', Rule::in(Member::ROLES)],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:254'],
            'area' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', Rule::in(Member::STATUSES)],
            'join_date' => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'username.required' => 'Vui lòng nhập tên đăng nhập.',
            'username.unique' => 'Tên đăng nhập đã được sử dụng.',
            'username.regex' => 'Tên đăng nhập chỉ gồm chữ thường, số, dấu chấm, gạch ngang hoặc gạch dưới.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu tối thiểu 6 ký tự.',
            'role.required' => 'Vui lòng chọn vai trò.',
            'role.in' => 'Vai trò phải là Giám đốc hoặc CTV.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'email.email' => 'Email không hợp lệ.',
        ];
    }
}
