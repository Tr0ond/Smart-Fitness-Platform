<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaiTap;
use App\Models\BaiTapTrongKeHoach;
use App\Models\HiepTap;
use App\Models\PhienTap;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BaiTapTrongPhien extends Model
{
    protected $table = 'bai_tap_trong_phien';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'phien_tap_id',
        'bai_tap_id',
        'bai_tap_trong_ke_hoach_id',
        'ma_bai_thuc_hien',
        'so_thu_tu',
        'ten_bai_tap',
        'huong_dan',
        'dung_cu_su_dung',
        'so_hiep_du_kien',
        'so_lan_lap_du_kien_toi_thieu',
        'so_lan_lap_du_kien_toi_da',
        'khoi_luong_du_kien_kg',
        'thoi_gian_nghi_du_kien_giay',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'phien_tap_id' => 'integer',
        'bai_tap_id' => 'integer',
        'bai_tap_trong_ke_hoach_id' => 'integer',
        'so_thu_tu' => 'integer',
        'dung_cu_su_dung' => 'array',
        'so_hiep_du_kien' => 'integer',
        'so_lan_lap_du_kien_toi_thieu' => 'integer',
        'so_lan_lap_du_kien_toi_da' => 'integer',
        'khoi_luong_du_kien_kg' => 'decimal:2',
        'thoi_gian_nghi_du_kien_giay' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function phienTap(): BelongsTo
    {
        return $this->belongsTo(PhienTap::class, 'phien_tap_id', 'id');
    }

    public function baiTap(): BelongsTo
    {
        return $this->belongsTo(BaiTap::class, 'bai_tap_id', 'id');
    }

    public function baiTapTrongKeHoach(): BelongsTo
    {
        return $this->belongsTo(BaiTapTrongKeHoach::class, 'bai_tap_trong_ke_hoach_id', 'id');
    }

    public function hiepTaps(): HasMany
    {
        return $this->hasMany(HiepTap::class, 'bai_tap_trong_phien_id', 'id');
    }
}
