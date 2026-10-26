<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('working_hours') && ! $this->exists('workingHours')) {
            $this->merge([
                'workingHours' => $this->input('working_hours'),
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
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:254'],
            'address' => ['required', 'string', 'max:500'],
            'workingHours' => ['required', 'string', 'max:1000'],
            'website' => ['nullable', 'string', 'max:160'],
            'slogan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên công ty.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'address.required' => 'Vui lòng nhập địa chỉ trụ sở.',
            'workingHours.required' => 'Vui lòng nhập thời gian làm việc.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'address' => $data['address'],
            'working_hours' => $data['workingHours'],
            'website' => $data['website'] ?? null,
            'slogan' => $data['slogan'] ?? null,
        ];
    }
}
