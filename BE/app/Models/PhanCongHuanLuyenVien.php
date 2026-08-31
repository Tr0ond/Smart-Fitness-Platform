<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PhanCongHuanLuyenVien extends Model
{
    protected $table = 'phan_cong_huan_luyen_vien';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'huan_luyen_vien_id',
        'nguoi_phan_cong_id',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'ly_do_ket_thuc',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'huan_luyen_vien_id' => 'integer',
        'nguoi_phan_cong_id' => 'integer',
        'ngay_bat_dau' => 'datetime',
        'ngay_ket_thuc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function huanLuyenVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHuanLuyenVien::class, 'huan_luyen_vien_id', 'id');
    }

    public function nguoiPhanCong(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_phan_cong_id', 'id');
    }

    public function deXuatKeHoachTaps(): HasMany
    {
        return $this->hasMany(DeXuatKeHoachTap::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }

    public function ghiChuHuanLuyens(): HasMany
    {
        return $this->hasMany(GhiChuHuanLuyen::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }

    public function hoiThoai(): HasOne
    {
        return $this->hasOne(HoiThoai::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }

    public function lichSuSuDungHuanLuyenViens(): HasMany
    {
        return $this->hasMany(LichSuSuDungHuanLuyenVien::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }

    public function tinNhans(): HasMany
    {
        return $this->hasMany(TinNhan::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }
}
