<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\KyHanHoiVien;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\SuDungQuyenLoi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LichSuSuDungHuanLuyenVien extends Model
{
    protected $table = 'lich_su_su_dung_huan_luyen_vien';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = false;

    protected $fillable = [
        'hoi_vien_id',
        'huan_luyen_vien_id',
        'phan_cong_huan_luyen_vien_id',
        'ky_han_hoi_vien_id',
        'su_dung_quyen_loi_id',
        'ma_buoi_huan_luyen',
        'trang_thai',
        'so_luot_su_dung',
        'hoan_thanh_luc',
        'xac_nhan_luc',
        'nguon_thao_tac',
        'ghi_chu',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'huan_luyen_vien_id' => 'integer',
        'phan_cong_huan_luyen_vien_id' => 'integer',
        'ky_han_hoi_vien_id' => 'integer',
        'su_dung_quyen_loi_id' => 'integer',
        'so_luot_su_dung' => 'integer',
        'hoan_thanh_luc' => 'datetime',
        'xac_nhan_luc' => 'datetime',
        'ngay_tao' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function huanLuyenVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHuanLuyenVien::class, 'huan_luyen_vien_id', 'id');
    }

    public function phanCongHuanLuyenVien(): BelongsTo
    {
        return $this->belongsTo(PhanCongHuanLuyenVien::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }

    public function kyHanHoiVien(): BelongsTo
    {
        return $this->belongsTo(KyHanHoiVien::class, 'ky_han_hoi_vien_id', 'id');
    }

    public function suDungQuyenLoi(): BelongsTo
    {
        return $this->belongsTo(SuDungQuyenLoi::class, 'su_dung_quyen_loi_id', 'id');
    }
}
