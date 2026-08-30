<?php

namespace App\Http\Requests\Pt;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class SendPtChatMessageRequest extends FormRequest
{
    private const TEXT_MAX_BYTES = 65_535;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, Closure|string>> */
    public function rules(): array
    {
        return [
            'client_message_id' => ['required', 'uuid'],
            'content' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && strlen($value) > self::TEXT_MAX_BYTES) {
                        $fail('Nội dung tin nhắn vượt giới hạn vật lý của trường TEXT.');
                    }
                },
            ],
            'sender_id' => ['prohibited'],
            'nguoi_gui_id' => ['prohibited'],
            'member_id' => ['prohibited'],
            'trainer_id' => ['prohibited'],
            'pt_id' => ['prohibited'],
            'assignment_id' => ['prohibited'],
            'conversation_id' => ['prohibited'],
            'usage_id' => ['prohibited'],
            'membership_term_id' => ['prohibited'],
            'sequence' => ['prohibited'],
            'status' => ['prohibited'],
            'sent_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'broadcast_status' => ['prohibited'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('content'))) {
            $this->merge(['content' => trim($this->input('content'))]);
        }
    }
}
