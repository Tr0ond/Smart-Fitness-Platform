<?php

namespace App\Auth;

use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;

class AccessTokenGuard extends RequestGuard
{
    /**
     * Đổi request đồng thời xóa user đã cache, bắt buộc token mới được kiểm tra
     * lại trong test kernel và các worker có vòng đời dài.
     */
    public function setRequest(Request $request): static
    {
        $this->user = null;
        parent::setRequest($request);

        return $this;
    }
}
