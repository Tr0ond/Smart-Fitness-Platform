<?php

namespace App\Http\Requests\Auth;

use App\Support\EmailCanonicalizer;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'password' => ['required', 'string', 'max:4096'],
            'device_name' => ['sometimes', 'string', 'max:150'],
        ];
    }

    /** Chuẩn hóa email trước khi Laravel kiểm tra định dạng và Controller lookup. */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge([
                'email' => app(EmailCanonicalizer::class)->chuanHoa($this->input('email')),
            ]);
        }
    }
}
