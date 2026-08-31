<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BaiTapTrongKeHoach extends Model
{
    protected $table = 'bai_tap_trong_ke_hoach';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = false;

    protected $fillable = [
        'ngay_trong_ke_hoach_id',
        'bai_tap_id',
        'ma_bai_logic',
        'so_thu_tu',
        'ten_bai_tap',
        'huong_dan',
        'dung_cu_yeu_cau',
        'so_hiep_muc_tieu',
        'so_lan_lap_toi_thieu',
        'so_lan_lap_toi_da',
        'khoi_luong_muc_tieu_kg',
        'thoi_gian_nghi_giay',
        'ghi_chu',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'ngay_trong_ke_hoach_id' => 'integer',
        'bai_tap_id' => 'integer',
        'so_thu_tu' => 'integer',
        'dung_cu_yeu_cau' => 'array',
        'so_hiep_muc_tieu' => 'integer',
        'so_lan_lap_toi_thieu' => 'integer',
        'so_lan_lap_toi_da' => 'integer',
        'khoi_luong_muc_tieu_kg' => 'decimal:2',
        'thoi_gian_nghi_giay' => 'integer',
        'ngay_tao' => 'datetime',
    ];

    public function ngayTrongKeHoach(): BelongsTo
    {
        return $this->belongsTo(NgayTrongKeHoach::class, 'ngay_trong_ke_hoach_id', 'id');
    }

    public function baiTap(): BelongsTo
    {
        return $this->belongsTo(BaiTap::class, 'bai_tap_id', 'id');
    }

    public function baiTapTrongPhiens(): HasMany
    {
        return $this->hasMany(BaiTapTrongPhien::class, 'bai_tap_trong_ke_hoach_id', 'id');
    }
}
