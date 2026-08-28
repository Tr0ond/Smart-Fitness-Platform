<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ChiNhanh;
use App\Models\HoSoHoiVien;
use App\Models\KyHanHoiVien;
use App\Models\MaVaoPhongTap;
use App\Models\NguoiDung;
use App\Models\SuDungQuyenLoi;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LichSuVaoPhongTap extends Model
{
    protected $table = 'lich_su_vao_phong_tap';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = false;

    protected $fillable = [
        'ma_vao_phong_tap_id',
        'hoi_vien_id',
        'chi_nhanh_id',
        'ky_han_hoi_vien_id',
        'su_dung_quyen_loi_id',
        'nguoi_xac_nhan_id',
        'vao_phong_luc',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'ma_vao_phong_tap_id' => 'integer',
        'hoi_vien_id' => 'integer',
        'chi_nhanh_id' => 'integer',
        'ky_han_hoi_vien_id' => 'integer',
        'su_dung_quyen_loi_id' => 'integer',
        'nguoi_xac_nhan_id' => 'integer',
        'vao_phong_luc' => 'datetime',
        'ngay_tao' => 'datetime',
    ];

    public function maVaoPhongTap(): BelongsTo
    {
        return $this->belongsTo(MaVaoPhongTap::class, 'ma_vao_phong_tap_id', 'id');
    }

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function chiNhanh(): BelongsTo
    {
        return $this->belongsTo(ChiNhanh::class, 'chi_nhanh_id', 'id');
    }

    public function kyHanHoiVien(): BelongsTo
    {
        return $this->belongsTo(KyHanHoiVien::class, 'ky_han_hoi_vien_id', 'id');
    }

    public function suDungQuyenLoi(): BelongsTo
    {
        return $this->belongsTo(SuDungQuyenLoi::class, 'su_dung_quyen_loi_id', 'id');
    }

    public function nguoiXacNhan(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_xac_nhan_id', 'id');
    }
}
