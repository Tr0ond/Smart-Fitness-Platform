<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HoSoHoiVien;
use App\Models\KeHoachTap;
use App\Models\NgayTrongKeHoach;
use App\Models\PhienBanKeHoachTap;
use App\Models\PhienTap;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BuoiTapDuKien extends Model
{
    protected $table = 'buoi_tap_du_kien';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'ke_hoach_tap_id',
        'phien_ban_ke_hoach_tap_id',
        'ngay_trong_ke_hoach_id',
        'ma_buoi_logic',
        'ngay_tap',
        'gio_bat_dau_du_kien',
        'gio_ket_thuc_du_kien',
        'trang_thai',
        'thay_the_buoi_tap_id',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'ke_hoach_tap_id' => 'integer',
        'phien_ban_ke_hoach_tap_id' => 'integer',
        'ngay_trong_ke_hoach_id' => 'integer',
        'ngay_tap' => 'date',
        'thay_the_buoi_tap_id' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function keHoachTap(): BelongsTo
    {
        return $this->belongsTo(KeHoachTap::class, 'ke_hoach_tap_id', 'id');
    }

    public function phienBanKeHoachTap(): BelongsTo
    {
        return $this->belongsTo(PhienBanKeHoachTap::class, 'phien_ban_ke_hoach_tap_id', 'id');
    }

    public function ngayTrongKeHoach(): BelongsTo
    {
        return $this->belongsTo(NgayTrongKeHoach::class, 'ngay_trong_ke_hoach_id', 'id');
    }

    public function thayTheBuoiTap(): BelongsTo
    {
        return $this->belongsTo(BuoiTapDuKien::class, 'thay_the_buoi_tap_id', 'id');
    }

    public function buoiTapDuKien(): HasOne
    {
        return $this->hasOne(BuoiTapDuKien::class, 'thay_the_buoi_tap_id', 'id');
    }

    public function phienTap(): HasOne
    {
        return $this->hasOne(PhienTap::class, 'buoi_tap_du_kien_id', 'id');
    }
}
