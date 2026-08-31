<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KyHanHoiVien extends Model
{
    protected $table = 'ky_han_hoi_vien';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'don_mua_goi_id',
        'lan_thanh_toan_id',
        'dang_ky_goi_tap_id',
        'so_thu_tu',
        'trang_thai',
        'ten_goi',
        'phien_ban_goi',
        'gia_da_mua',
        'thoi_han_ngay',
        'cho_phep_vao_phong_tap',
        'cho_phep_tro_ly_tap_luyen',
        'gioi_han_luot_tro_ly',
        'cho_phep_tro_chuyen_huan_luyen_vien',
        'so_buoi_huan_luyen_vien',
        'so_buoi_huan_luyen_vien_da_dung',
        'so_luot_tro_ly_da_dung',
        'so_luot_tro_ly_giu_cho',
        'mua_luc',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'don_mua_goi_id' => 'integer',
        'lan_thanh_toan_id' => 'integer',
        'dang_ky_goi_tap_id' => 'integer',
        'so_thu_tu' => 'integer',
        'phien_ban_goi' => 'integer',
        'gia_da_mua' => 'decimal:0',
        'thoi_han_ngay' => 'integer',
        'cho_phep_vao_phong_tap' => 'boolean',
        'cho_phep_tro_ly_tap_luyen' => 'boolean',
        'gioi_han_luot_tro_ly' => 'integer',
        'cho_phep_tro_chuyen_huan_luyen_vien' => 'boolean',
        'so_buoi_huan_luyen_vien' => 'integer',
        'so_buoi_huan_luyen_vien_da_dung' => 'integer',
        'so_luot_tro_ly_da_dung' => 'integer',
        'so_luot_tro_ly_giu_cho' => 'integer',
        'mua_luc' => 'datetime',
        'ngay_bat_dau' => 'datetime',
        'ngay_ket_thuc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function donMuaGoi(): BelongsTo
    {
        return $this->belongsTo(DonMuaGoi::class, 'don_mua_goi_id', 'id');
    }

    public function lanThanhToan(): BelongsTo
    {
        return $this->belongsTo(LanThanhToan::class, 'lan_thanh_toan_id', 'id');
    }

    public function dangKyGoiTap(): BelongsTo
    {
        return $this->belongsTo(DangKyGoiTap::class, 'dang_ky_goi_tap_id', 'id');
    }

    public function lichSuSuDungHuanLuyenViens(): HasMany
    {
        return $this->hasMany(LichSuSuDungHuanLuyenVien::class, 'ky_han_hoi_vien_id', 'id');
    }

    public function lichSuVaoPhongTaps(): HasMany
    {
        return $this->hasMany(LichSuVaoPhongTap::class, 'ky_han_hoi_vien_id', 'id');
    }

    public function suDungQuyenLois(): HasMany
    {
        return $this->hasMany(SuDungQuyenLoi::class, 'ky_han_hoi_vien_id', 'id');
    }

    public function yeuCauTroLys(): HasMany
    {
        return $this->hasMany(YeuCauTroLy::class, 'ky_han_hoi_vien_id', 'id');
    }
}
