<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaiTapUngVien;
use App\Models\DeXuatKeHoachTap;
use App\Models\GiaoAnUngVien;
use App\Models\HoSoHoiVien;
use App\Models\HoiThoaiTroLy;
use App\Models\KyHanHoiVien;
use App\Models\LanGoiMoHinh;
use App\Models\SuDungQuyenLoi;
use App\Models\TinNhanTroLy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class YeuCauTroLy extends Model
{
    protected $table = 'yeu_cau_tro_ly';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'hoi_thoai_tro_ly_id',
        'tin_nhan_dau_vao_id',
        'ky_han_hoi_vien_id',
        'su_dung_quyen_loi_id',
        'ma_yeu_cau',
        'loai_yeu_cau',
        'yeu_cau_chuan_hoa',
        'ngu_canh_da_chot',
        'phien_ban_quy_tac',
        'trang_thai',
        'trang_thai_han_muc',
        'bat_dau_xu_ly_luc',
        'hoan_tat_luc',
        'ma_loi',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'hoi_thoai_tro_ly_id' => 'integer',
        'tin_nhan_dau_vao_id' => 'integer',
        'ky_han_hoi_vien_id' => 'integer',
        'su_dung_quyen_loi_id' => 'integer',
        'yeu_cau_chuan_hoa' => 'array',
        'ngu_canh_da_chot' => 'array',
        'bat_dau_xu_ly_luc' => 'datetime',
        'hoan_tat_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function hoiThoaiTroLy(): BelongsTo
    {
        return $this->belongsTo(HoiThoaiTroLy::class, 'hoi_thoai_tro_ly_id', 'id');
    }

    public function tinNhanDauVao(): BelongsTo
    {
        return $this->belongsTo(TinNhanTroLy::class, 'tin_nhan_dau_vao_id', 'id');
    }

    public function kyHanHoiVien(): BelongsTo
    {
        return $this->belongsTo(KyHanHoiVien::class, 'ky_han_hoi_vien_id', 'id');
    }

    public function suDungQuyenLoi(): BelongsTo
    {
        return $this->belongsTo(SuDungQuyenLoi::class, 'su_dung_quyen_loi_id', 'id');
    }

    public function baiTapUngViens(): HasMany
    {
        return $this->hasMany(BaiTapUngVien::class, 'yeu_cau_tro_ly_id', 'id');
    }

    public function deXuatKeHoachTap(): HasOne
    {
        return $this->hasOne(DeXuatKeHoachTap::class, 'yeu_cau_tro_ly_id', 'id');
    }

    public function giaoAnUngViens(): HasMany
    {
        return $this->hasMany(GiaoAnUngVien::class, 'yeu_cau_tro_ly_id', 'id');
    }

    public function lanGoiMoHinhs(): HasMany
    {
        return $this->hasMany(LanGoiMoHinh::class, 'yeu_cau_tro_ly_id', 'id');
    }

    public function tinNhanTroLys(): HasMany
    {
        return $this->hasMany(TinNhanTroLy::class, 'yeu_cau_tro_ly_id', 'id');
    }
}
