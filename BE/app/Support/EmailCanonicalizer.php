<?php

namespace App\Support;

use Normalizer;

class EmailCanonicalizer
{
    /**
     * Chuẩn hóa định danh email trước validation, lưu và lookup.
     *
     * Input: email do client gửi. Cách xử lý: trim, Unicode NFC và lowercase
     * toàn địa chỉ. Output: email canonical; không sửa dữ liệu trong Database.
     */
    public function chuanHoa(string $thuDienTu): string
    {
        $thuDienTu = trim($thuDienTu);

        if (class_exists(Normalizer::class)) {
            $thuDienTu = Normalizer::normalize($thuDienTu, Normalizer::FORM_C) ?: $thuDienTu;
        }

        return mb_strtolower($thuDienTu, 'UTF-8');
    }
}
