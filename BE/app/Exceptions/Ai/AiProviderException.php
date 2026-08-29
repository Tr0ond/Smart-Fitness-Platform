<?php

namespace App\Exceptions\Ai;

class AiProviderException extends AiWorkflowException
{
    public static function timeout(): self
    {
        return new self('Nhà cung cấp AI phản hồi quá hạn.', 503, 'AI_PROVIDER_TIMEOUT', 'QUA_HAN');
    }

    public static function rateLimited(): self
    {
        return new self('Nhà cung cấp AI đang giới hạn lưu lượng.', 503, 'AI_PROVIDER_RATE_LIMITED', 'THAT_BAI');
    }

    public static function serverError(): self
    {
        return new self('Nhà cung cấp AI tạm thời không khả dụng.', 503, 'AI_PROVIDER_UNAVAILABLE', 'THAT_BAI');
    }

    public static function malformedOutput(): self
    {
        return new self('Nhà cung cấp AI trả dữ liệu không đúng cấu trúc.', 502, 'AI_MALFORMED_OUTPUT', 'LOI_CAU_TRUC');
    }
}
