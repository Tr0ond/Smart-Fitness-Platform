<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaiTapTrongPhien;
use App\Models\BuoiTapDuKien;
use App\Models\GhiChuHuanLuyen;
use App\Models\HoSoHoiVien;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PhienTap extends Model
{
    protected $table = 'phien_tap';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'buoi_tap_du_kien_id',
        'ma_lan_bat_dau',
        'ten_buoi_tap',
        'bat_dau_luc',
        'ket_thuc_luc',
        'trang_thai',
        'ghi_chu',
        'phien_ban_du_lieu',
        'ma_lan_hoan_thanh',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'buoi_tap_du_kien_id' => 'integer',
        'bat_dau_luc' => 'datetime',
        'ket_thuc_luc' => 'datetime',
        'phien_ban_du_lieu' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function buoiTapDuKien(): BelongsTo
    {
        return $this->belongsTo(BuoiTapDuKien::class, 'buoi_tap_du_kien_id', 'id');
    }

    public function baiTapTrongPhiens(): HasMany
    {
        return $this->hasMany(BaiTapTrongPhien::class, 'phien_tap_id', 'id');
    }

    public function ghiChuHuanLuyens(): HasMany
    {
        return $this->hasMany(GhiChuHuanLuyen::class, 'phien_tap_id', 'id');
    }
}
