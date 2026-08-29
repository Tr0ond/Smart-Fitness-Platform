<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NguoiDung extends Model implements AuthenticatableContract
{
    use AuthenticatableTrait;

    protected $table = 'nguoi_dung';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'chi_nhanh_id',
        'ho_ten',
        'thu_dien_tu',
        'so_dien_thoai',
        'mat_khau_bam',
        'anh_dai_dien',
        'xac_minh_thu_luc',
        'trang_thai',
        'dang_nhap_gan_nhat_luc',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $hidden = [
        'mat_khau_bam',
    ];

    protected $casts = [
        'id' => 'integer',
        'chi_nhanh_id' => 'integer',
        'xac_minh_thu_luc' => 'datetime',
        'dang_nhap_gan_nhat_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    /** Tên cột hash mật khẩu chính thức dùng bởi Laravel authentication. */
    public function getAuthPasswordName(): string
    {
        return 'mat_khau_bam';
    }

    /** Schema không có remember token vì API dùng Bearer token. */
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function chiNhanh(): BelongsTo
    {
        return $this->belongsTo(ChiNhanh::class, 'chi_nhanh_id', 'id');
    }

    public function baiTaps(): HasMany
    {
        return $this->hasMany(BaiTap::class, 'nguoi_tao_id', 'id');
    }

    public function deXuatKeHoachTapsTheoNguoiTao(): HasMany
    {
        return $this->hasMany(DeXuatKeHoachTap::class, 'nguoi_tao_id', 'id');
    }

    public function deXuatKeHoachTapsTheoNguoiQuyetDinh(): HasMany
    {
        return $this->hasMany(DeXuatKeHoachTap::class, 'nguoi_quyet_dinh_id', 'id');
    }

    public function ghiChuHuanLuyens(): HasMany
    {
        return $this->hasMany(GhiChuHuanLuyen::class, 'nguoi_tao_id', 'id');
    }

    public function giaoAnMaus(): HasMany
    {
        return $this->hasMany(GiaoAnMau::class, 'nguoi_tao_id', 'id');
    }

    public function goiTaps(): HasMany
    {
        return $this->hasMany(GoiTap::class, 'nguoi_tao_id', 'id');
    }

    public function hoSoHoiVien(): HasOne
    {
        return $this->hasOne(HoSoHoiVien::class, 'nguoi_dung_id', 'id');
    }

    public function hoSoHuanLuyenVien(): HasOne
    {
        return $this->hasOne(HoSoHuanLuyenVien::class, 'nguoi_dung_id', 'id');
    }

    public function keHoachTaps(): HasMany
    {
        return $this->hasMany(KeHoachTap::class, 'nguoi_tao_id', 'id');
    }

    public function lichSuVaoPhongTaps(): HasMany
    {
        return $this->hasMany(LichSuVaoPhongTap::class, 'nguoi_xac_nhan_id', 'id');
    }

    public function nhatKyHeThongs(): HasMany
    {
        return $this->hasMany(NhatKyHeThong::class, 'nguoi_thuc_hien_id', 'id');
    }

    public function phanCongHuanLuyenViens(): HasMany
    {
        return $this->hasMany(PhanCongHuanLuyenVien::class, 'nguoi_phan_cong_id', 'id');
    }

    public function phanQuyenNguoiDungsTheoNguoiDung(): HasMany
    {
        return $this->hasMany(PhanQuyenNguoiDung::class, 'nguoi_dung_id', 'id');
    }

    public function phanQuyenNguoiDungsTheoNguoiCap(): HasMany
    {
        return $this->hasMany(PhanQuyenNguoiDung::class, 'nguoi_cap_id', 'id');
    }

    public function phienBanKeHoachTaps(): HasMany
    {
        return $this->hasMany(PhienBanKeHoachTap::class, 'nguoi_tao_id', 'id');
    }

    public function suDungQuyenLois(): HasMany
    {
        return $this->hasMany(SuDungQuyenLoi::class, 'nguoi_thuc_hien_id', 'id');
    }

    public function theTruyCaps(): HasMany
    {
        return $this->hasMany(TheTruyCap::class, 'nguoi_dung_id', 'id');
    }

    public function tinNhans(): HasMany
    {
        return $this->hasMany(TinNhan::class, 'nguoi_gui_id', 'id');
    }

    public function yeuCauChongLaps(): HasMany
    {
        return $this->hasMany(YeuCauChongLap::class, 'nguoi_dung_id', 'id');
    }

    public function yeuCauDatLaiMatKhaus(): HasMany
    {
        return $this->hasMany(YeuCauDatLaiMatKhau::class, 'nguoi_dung_id', 'id');
    }
}
