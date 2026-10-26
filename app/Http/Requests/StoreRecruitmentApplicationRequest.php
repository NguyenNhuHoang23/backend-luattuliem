<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecruitmentApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $aliases = [
            'fullName' => 'full_name',
            'bio' => 'experience',
        ];
        $merge = [];

        foreach ($aliases as $from => $to) {
            if ($this->exists($from) && ! $this->exists($to)) {
                $merge[$to] = $this->input($from);
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
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:254'],
            'role' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:250'],
            'experience' => ['required', 'string', 'max:4000'],
            'cv' => ['required', 'file', 'mimes:pdf', 'max:25600'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'role.required' => 'Vui lòng chọn vị trí ứng tuyển.',
            'area.required' => 'Vui lòng nhập phạm vi hoạt động.',
            'experience.required' => 'Vui lòng nhập giới thiệu ngắn.',
            'cv.required' => 'Vui lòng đính kèm CV định dạng PDF.',
            'cv.mimes' => 'CV phải là file PDF.',
            'cv.max' => 'CV vượt quá 25 MB. Vui lòng chọn file nhỏ hơn.',
        ];
    }
}
