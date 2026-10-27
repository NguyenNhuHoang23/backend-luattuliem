<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveMemberApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $username = $this->input('username');
        if (is_string($username)) {
            $this->merge(['username' => strtolower(trim($username))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('members', 'username'),
            ],
            'password' => ['required', 'string', 'min:6', 'max:72'],
            'area' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Vui lòng nhập tên đăng nhập để cấp tài khoản.',
            'username.unique' => 'Tên đăng nhập đã được sử dụng.',
            'username.regex' => 'Tên đăng nhập chỉ gồm chữ thường, số, dấu chấm, gạch ngang hoặc gạch dưới.',
            'password.required' => 'Vui lòng nhập mật khẩu để cấp tài khoản.',
            'password.min' => 'Mật khẩu tối thiểu 6 ký tự.',
        ];
    }
}
