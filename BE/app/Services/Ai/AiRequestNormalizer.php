<?php

namespace App\Services\Ai;

use App\Exceptions\Ai\AiWorkflowException;
use Normalizer;

class AiRequestNormalizer
{
    private const CAC_LOAI_HO_TRO = ['TAO_KE_HOACH', 'DIEU_CHINH', 'THAY_BAI'];

    /**
     * Chuẩn hóa payload AI để validation nghiệp vụ và idempotency cùng dùng.
     *
     * Input chỉ gồm loại yêu cầu và prompt; mọi owner/context quan trọng được
     * Backend suy ra. Hàm loại khoảng trắng thừa, chuẩn hóa Unicode NFC nếu có
     * extension intl và từ chối rõ nội dung ngoài workout planning.
     *
     * @param  array<string, mixed>  $input
     * @return array{loai_yeu_cau: string, prompt: string}
     */
    public function chuanHoa(array $input): array
    {
        $loai = strtoupper(trim((string) ($input['request_type'] ?? '')));
        $prompt = preg_replace('/\s+/u', ' ', trim((string) ($input['prompt'] ?? ''))) ?? '';
        if (class_exists(Normalizer::class)) {
            $prompt = Normalizer::normalize($prompt, Normalizer::FORM_C) ?: $prompt;
        }

        if (! in_array($loai, self::CAC_LOAI_HO_TRO, true)) {
            throw new AiWorkflowException(
                'Loại yêu cầu AI chưa được hỗ trợ trong phạm vi lập kế hoạch tập.',
                422,
                'AI_REQUEST_TYPE_UNSUPPORTED',
            );
        }
        if ($prompt === '') {
            throw new AiWorkflowException('Nội dung yêu cầu không được để trống.', 422, 'AI_PROMPT_REQUIRED');
        }
        if ($this->ngoaiPhamVi($prompt)) {
            throw new AiWorkflowException(
                'Yêu cầu nằm ngoài phạm vi workout planning của trợ lý.',
                422,
                'AI_REQUEST_OUT_OF_SCOPE',
            );
        }

        return ['loai_yeu_cau' => $loai, 'prompt' => $prompt];
    }

    private function ngoaiPhamVi(string $prompt): bool
    {
        return preg_match(
            '/\b(nutrition|diet|calorie|supplement|medication|medicine|diagnos(?:e|is)|disease|injury|steroid|ped|rehabilitation)\b|dinh\s*duong|dinh\s*dưỡng|thuc\s*don|thực\s*đơn|thuc\s*pham\s*bo\s*sung|thực\s*phẩm\s*bổ\s*sung|chan\s*thuong|chấn\s*thương|chuan\s*doan|chẩn\s*đoán|dieu\s*tri|điều\s*trị|thuoc\s*(?:men)?|thuốc\s*(?:men)?|phuc\s*hoi\s*y\s*khoa|phục\s*hồi\s*y\s*khoa/iu',
            $prompt,
        ) === 1;
    }
}
