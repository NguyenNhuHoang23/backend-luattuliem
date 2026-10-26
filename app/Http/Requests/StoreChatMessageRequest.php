<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:5000'],
            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,zip,txt'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'files.max' => 'Mỗi tin nhắn được đính kèm tối đa 5 file.',
            'files.*.max' => 'Mỗi file được đính kèm tối đa 10 MB.',
            'files.*.mimes' => 'Định dạng file không được hỗ trợ.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $content = trim((string) $this->input('content', ''));
            $files = $this->file('files', []);
            if ($files instanceof \Illuminate\Http\UploadedFile) {
                $files = [$files];
            }
            if ($content === '' && ($files === [] || $files === null)) {
                $validator->errors()->add('content', 'Vui lòng nhập nội dung hoặc đính kèm file.');
            }
        });
    }
}
