<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NgayTrongKeHoach extends Model
{
    protected $table = 'ngay_trong_ke_hoach';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = false;

    protected $fillable = [
        'phien_ban_ke_hoach_tap_id',
        'ma_ngay_logic',
        'so_thu_tu',
        'thu_trong_tuan',
        'ten_ngay',
        'thoi_luong_du_kien_phut',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'phien_ban_ke_hoach_tap_id' => 'integer',
        'so_thu_tu' => 'integer',
        'thu_trong_tuan' => 'integer',
        'thoi_luong_du_kien_phut' => 'integer',
        'ngay_tao' => 'datetime',
    ];

    public function phienBanKeHoachTap(): BelongsTo
    {
        return $this->belongsTo(PhienBanKeHoachTap::class, 'phien_ban_ke_hoach_tap_id', 'id');
    }

    public function baiTapTrongKeHoachs(): HasMany
    {
        return $this->hasMany(BaiTapTrongKeHoach::class, 'ngay_trong_ke_hoach_id', 'id');
    }

    public function buoiTapDuKiens(): HasMany
    {
        return $this->hasMany(BuoiTapDuKien::class, 'ngay_trong_ke_hoach_id', 'id');
    }
}
