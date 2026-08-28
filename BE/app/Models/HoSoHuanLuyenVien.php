<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HoiThoai;
use App\Models\LichSuSuDungHuanLuyenVien;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TinNhan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HoSoHuanLuyenVien extends Model
{
    protected $table = 'ho_so_huan_luyen_vien';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'nguoi_dung_id',
        'ma_huan_luyen_vien',
        'gioi_thieu',
        'chuyen_mon',
        'trang_thai',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'nguoi_dung_id' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id', 'id');
    }

    public function hoiThoais(): HasMany
    {
        return $this->hasMany(HoiThoai::class, 'huan_luyen_vien_id', 'id');
    }

    public function lichSuSuDungHuanLuyenViens(): HasMany
    {
        return $this->hasMany(LichSuSuDungHuanLuyenVien::class, 'huan_luyen_vien_id', 'id');
    }

    public function phanCongHuanLuyenViens(): HasMany
    {
        return $this->hasMany(PhanCongHuanLuyenVien::class, 'huan_luyen_vien_id', 'id');
    }

    public function tinNhans(): HasMany
    {
        return $this->hasMany(TinNhan::class, 'huan_luyen_vien_id', 'id');
    }
}
