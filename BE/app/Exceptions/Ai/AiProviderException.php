<?php

namespace App\Exceptions\Ai;

class AiProviderException extends AiWorkflowException
{
    public static function missingConfiguration(): self
    {
        return new self('Cấu hình nhà cung cấp AI chưa đầy đủ.', 503, 'AI_PROVIDER_CONFIGURATION_MISSING', 'THAT_BAI');
    }

    public static function unsupportedProvider(): self
    {
        return new self('Nhà cung cấp AI không được hỗ trợ.', 503, 'AI_PROVIDER_UNSUPPORTED', 'THAT_BAI');
    }

    public static function invalidRequest(): self
    {
        return new self('Nhà cung cấp AI từ chối yêu cầu kỹ thuật.', 503, 'AI_PROVIDER_REQUEST_REJECTED', 'THAT_BAI');
    }

    public static function invalidCredentials(): self
    {
        return new self('Không thể xác thực với nhà cung cấp AI.', 503, 'AI_PROVIDER_AUTHENTICATION_FAILED', 'THAT_BAI');
    }

    public static function networkError(): self
    {
        return new self('Không thể kết nối nhà cung cấp AI.', 503, 'AI_PROVIDER_NETWORK_ERROR', 'THAT_BAI');
    }

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

    public static function blockedOutput(): self
    {
        return new self('Nhà cung cấp AI không thể tạo kết quả cho yêu cầu này.', 502, 'AI_PROVIDER_OUTPUT_BLOCKED', 'LOI_CAU_TRUC');
    }
}
