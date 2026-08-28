<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuoiTapDuKien;
use App\Models\DeXuatKeHoachTap;
use App\Models\GhiChuHuanLuyen;
use App\Models\HoSoHoiVien;
use App\Models\NguoiDung;
use App\Models\PhienBanKeHoachTap;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KeHoachTap extends Model
{
    protected $table = 'ke_hoach_tap';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'ten_ke_hoach',
        'trang_thai',
        'phien_ban_hien_tai_id',
        'nguoi_tao_id',
        'ma_lan_tao',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'phien_ban_hien_tai_id' => 'integer',
        'nguoi_tao_id' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function phienBanHienTai(): BelongsTo
    {
        return $this->belongsTo(PhienBanKeHoachTap::class, 'phien_ban_hien_tai_id', 'id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id', 'id');
    }

    public function buoiTapDuKiens(): HasMany
    {
        return $this->hasMany(BuoiTapDuKien::class, 'ke_hoach_tap_id', 'id');
    }

    public function deXuatKeHoachTaps(): HasMany
    {
        return $this->hasMany(DeXuatKeHoachTap::class, 'ke_hoach_tap_id', 'id');
    }

    public function ghiChuHuanLuyens(): HasMany
    {
        return $this->hasMany(GhiChuHuanLuyen::class, 'ke_hoach_tap_id', 'id');
    }

    public function phienBanKeHoachTaps(): HasMany
    {
        return $this->hasMany(PhienBanKeHoachTap::class, 'ke_hoach_tap_id', 'id');
    }
}
