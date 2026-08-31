<?php

namespace App\Http\Requests\Auth;

use App\Support\EmailCanonicalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:254',
                Rule::unique('nguoi_dung', 'thu_dien_tu'),
            ],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'password' => [
                'required',
                'string',
                'max:4096',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
            'role' => ['prohibited'],
            'roles' => ['prohibited'],
            'vai_tro' => ['prohibited'],
            'vai_tro_id' => ['prohibited'],
            'ma_vai_tro' => ['prohibited'],
            'permissions' => ['prohibited'],
            'scopes' => ['prohibited'],
            'status' => ['prohibited'],
            'trang_thai' => ['prohibited'],
            'branch_id' => ['prohibited'],
            'chi_nhanh_id' => ['prohibited'],
            'nguoi_cap_id' => ['prohibited'],
            'member_code' => ['prohibited'],
            'ma_hoi_vien' => ['prohibited'],
            'mat_khau_bam' => ['prohibited'],
            'password_hash' => ['prohibited'],
            'xac_minh_thu_luc' => ['prohibited'],
            'email_verified_at' => ['prohibited'],
            'phien_ban_ho_so' => ['prohibited'],
            'moc_thay_doi_ke_hoach' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge([
                'email' => app(EmailCanonicalizer::class)->chuanHoa($this->input('email')),
            ]);
        }
    }
}
