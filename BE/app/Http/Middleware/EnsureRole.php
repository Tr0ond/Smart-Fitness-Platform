<?php

namespace App\Http\Middleware;

use App\Models\NguoiDung;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    private const CAC_VAI_TRO_CHINH_THUC = ['MEMBER', 'PT', 'RECEPTIONIST', 'ADMIN'];

    /**
     * Cho qua khi user có ít nhất một role được yêu cầu và chưa bị thu hồi.
     * Role được đọc lại từ Database trên mỗi request để việc thu hồi có hiệu lực
     * ngay cả khi Bearer token hiện tại vẫn hợp lệ.
     */
    public function handle(Request $request, Closure $next, string ...$vaiTroChoPhep): Response|JsonResponse
    {
        $nguoiDung = $request->user();

        if (! $nguoiDung instanceof NguoiDung) {
            return response()->json(['message' => 'Chưa xác thực.'], 401);
        }

        $vaiTroChoPhep = array_values(array_intersect(
            self::CAC_VAI_TRO_CHINH_THUC,
            array_map('strtoupper', $vaiTroChoPhep),
        ));

        $duocPhep = $vaiTroChoPhep !== []
            && $nguoiDung->phanQuyenNguoiDungsTheoNguoiDung()
                ->whereNull('thu_hoi_luc')
                ->whereHas('vaiTro', fn ($truyVan) => $truyVan->whereIn('ma_vai_tro', $vaiTroChoPhep))
                ->exists();

        if (! $duocPhep) {
            return response()->json(['message' => 'Không có quyền truy cập.'], 403);
        }

        return $next($request);
    }
}
