<?php

namespace App\Contracts\Ai;

use App\Data\Ai\WorkoutAiResult;

interface WorkoutAiProvider
{
    public function providerName(): string;

    public function modelName(): string;

    /**
     * Tạo kết quả kế hoạch có cấu trúc từ ngữ cảnh đã được Backend giới hạn.
     *
     * Provider chỉ nhận dữ liệu tập luyện đã lọc và các candidate do Rule Engine
     * cấp. Kết quả vẫn là dữ liệu không đáng tin và phải qua validator Backend.
     *
     * @param  array<string, mixed>  $context
     */
    public function generateStructuredProposal(array $context): WorkoutAiResult;
}
