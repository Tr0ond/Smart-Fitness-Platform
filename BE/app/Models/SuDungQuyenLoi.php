<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SuDungQuyenLoi extends Model
{
    protected $table = 'su_dung_quyen_loi';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = false;

    protected $fillable = [
        'hoi_vien_id',
        'ky_han_hoi_vien_id',
        'nguoi_thuc_hien_id',
        'loai_su_dung',
        'ma_hanh_dong',
        'chap_nhan_luc',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'ky_han_hoi_vien_id' => 'integer',
        'nguoi_thuc_hien_id' => 'integer',
        'chap_nhan_luc' => 'datetime',
        'ngay_tao' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function kyHanHoiVien(): BelongsTo
    {
        return $this->belongsTo(KyHanHoiVien::class, 'ky_han_hoi_vien_id', 'id');
    }

    public function nguoiThucHien(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_thuc_hien_id', 'id');
    }

    public function dangKyGoiTap(): HasOne
    {
        return $this->hasOne(DangKyGoiTap::class, 'lan_su_dung_dau_tien_id', 'id');
    }

    public function lichSuSuDungHuanLuyenVien(): HasOne
    {
        return $this->hasOne(LichSuSuDungHuanLuyenVien::class, 'su_dung_quyen_loi_id', 'id');
    }

    public function lichSuVaoPhongTap(): HasOne
    {
        return $this->hasOne(LichSuVaoPhongTap::class, 'su_dung_quyen_loi_id', 'id');
    }

    public function tinNhan(): HasOne
    {
        return $this->hasOne(TinNhan::class, 'su_dung_quyen_loi_id', 'id');
    }

    public function yeuCauTroLy(): HasOne
    {
        return $this->hasOne(YeuCauTroLy::class, 'su_dung_quyen_loi_id', 'id');
    }
}
