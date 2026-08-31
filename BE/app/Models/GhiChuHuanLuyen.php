<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GhiChuHuanLuyen extends Model
{
    protected $table = 'ghi_chu_huan_luyen';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = false;

    protected $fillable = [
        'hoi_vien_id',
        'phan_cong_huan_luyen_vien_id',
        'nguoi_tao_id',
        'ke_hoach_tap_id',
        'phien_tap_id',
        'noi_dung',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'phan_cong_huan_luyen_vien_id' => 'integer',
        'nguoi_tao_id' => 'integer',
        'ke_hoach_tap_id' => 'integer',
        'phien_tap_id' => 'integer',
        'ngay_tao' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function phanCongHuanLuyenVien(): BelongsTo
    {
        return $this->belongsTo(PhanCongHuanLuyenVien::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id', 'id');
    }

    public function keHoachTap(): BelongsTo
    {
        return $this->belongsTo(KeHoachTap::class, 'ke_hoach_tap_id', 'id');
    }

    public function phienTap(): BelongsTo
    {
        return $this->belongsTo(PhienTap::class, 'phien_tap_id', 'id');
    }
}
