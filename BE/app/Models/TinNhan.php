<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TinNhan extends Model
{
    protected $table = 'tin_nhan';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = false;

    protected $fillable = [
        'hoi_thoai_id',
        'so_thu_tu',
        'nguoi_gui_id',
        'phan_cong_huan_luyen_vien_id',
        'su_dung_quyen_loi_id',
        'ma_tin_nhan_phia_gui',
        'noi_dung',
        'gui_luc',
        'hoi_vien_id',
        'huan_luyen_vien_id',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_thoai_id' => 'integer',
        'so_thu_tu' => 'integer',
        'nguoi_gui_id' => 'integer',
        'phan_cong_huan_luyen_vien_id' => 'integer',
        'su_dung_quyen_loi_id' => 'integer',
        'gui_luc' => 'datetime',
        'hoi_vien_id' => 'integer',
        'huan_luyen_vien_id' => 'integer',
        'ngay_tao' => 'datetime',
    ];

    public function hoiThoai(): BelongsTo
    {
        return $this->belongsTo(HoiThoai::class, 'hoi_thoai_id', 'id');
    }

    public function nguoiGui(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_gui_id', 'id');
    }

    public function phanCongHuanLuyenVien(): BelongsTo
    {
        return $this->belongsTo(PhanCongHuanLuyenVien::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }

    public function suDungQuyenLoi(): BelongsTo
    {
        return $this->belongsTo(SuDungQuyenLoi::class, 'su_dung_quyen_loi_id', 'id');
    }

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function huanLuyenVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHuanLuyenVien::class, 'huan_luyen_vien_id', 'id');
    }

    public function suKienPhatTinNhan(): HasOne
    {
        return $this->hasOne(SuKienPhatTinNhan::class, 'tin_nhan_id', 'id');
    }
}
