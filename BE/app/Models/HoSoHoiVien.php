<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuoiTapDuKien;
use App\Models\ChiSoCoThe;
use App\Models\DangKyGoiTap;
use App\Models\DeXuatKeHoachTap;
use App\Models\DonMuaGoi;
use App\Models\DungCuHoiVien;
use App\Models\GhiChuHuanLuyen;
use App\Models\HoiThoai;
use App\Models\HoiThoaiTroLy;
use App\Models\KeHoachTap;
use App\Models\KyHanHoiVien;
use App\Models\LichSuSuDungHuanLuyenVien;
use App\Models\LichSuVaoPhongTap;
use App\Models\MaVaoPhongTap;
use App\Models\NgayRanhHoiVien;
use App\Models\NguoiDung;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\PhienTap;
use App\Models\SuDungQuyenLoi;
use App\Models\TinNhan;
use App\Models\YeuCauTroLy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HoSoHoiVien extends Model
{
    protected $table = 'ho_so_hoi_vien';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'nguoi_dung_id',
        'ma_hoi_vien',
        'ngay_sinh',
        'gioi_tinh',
        'muc_tieu_tap_luyen',
        'kinh_nghiem_tap_luyen',
        'so_ngay_tap_mong_muon',
        'thoi_luong_moi_buoi_phut',
        'phien_ban_ho_so',
        'moc_thay_doi_ke_hoach',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'nguoi_dung_id' => 'integer',
        'ngay_sinh' => 'date',
        'so_ngay_tap_mong_muon' => 'integer',
        'thoi_luong_moi_buoi_phut' => 'integer',
        'phien_ban_ho_so' => 'integer',
        'moc_thay_doi_ke_hoach' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id', 'id');
    }

    public function buoiTapDuKiens(): HasMany
    {
        return $this->hasMany(BuoiTapDuKien::class, 'hoi_vien_id', 'id');
    }

    public function chiSoCoThes(): HasMany
    {
        return $this->hasMany(ChiSoCoThe::class, 'hoi_vien_id', 'id');
    }

    public function dangKyGoiTaps(): HasMany
    {
        return $this->hasMany(DangKyGoiTap::class, 'hoi_vien_id', 'id');
    }

    public function deXuatKeHoachTaps(): HasMany
    {
        return $this->hasMany(DeXuatKeHoachTap::class, 'hoi_vien_id', 'id');
    }

    public function donMuaGois(): HasMany
    {
        return $this->hasMany(DonMuaGoi::class, 'hoi_vien_id', 'id');
    }

    public function dungCuHoiViens(): HasMany
    {
        return $this->hasMany(DungCuHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function ghiChuHuanLuyens(): HasMany
    {
        return $this->hasMany(GhiChuHuanLuyen::class, 'hoi_vien_id', 'id');
    }

    public function hoiThoais(): HasMany
    {
        return $this->hasMany(HoiThoai::class, 'hoi_vien_id', 'id');
    }

    public function hoiThoaiTroLys(): HasMany
    {
        return $this->hasMany(HoiThoaiTroLy::class, 'hoi_vien_id', 'id');
    }

    public function keHoachTaps(): HasMany
    {
        return $this->hasMany(KeHoachTap::class, 'hoi_vien_id', 'id');
    }

    public function kyHanHoiViens(): HasMany
    {
        return $this->hasMany(KyHanHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function lichSuSuDungHuanLuyenViens(): HasMany
    {
        return $this->hasMany(LichSuSuDungHuanLuyenVien::class, 'hoi_vien_id', 'id');
    }

    public function lichSuVaoPhongTaps(): HasMany
    {
        return $this->hasMany(LichSuVaoPhongTap::class, 'hoi_vien_id', 'id');
    }

    public function maVaoPhongTaps(): HasMany
    {
        return $this->hasMany(MaVaoPhongTap::class, 'hoi_vien_id', 'id');
    }

    public function ngayRanhHoiViens(): HasMany
    {
        return $this->hasMany(NgayRanhHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function phanCongHuanLuyenViens(): HasMany
    {
        return $this->hasMany(PhanCongHuanLuyenVien::class, 'hoi_vien_id', 'id');
    }

    public function phienTaps(): HasMany
    {
        return $this->hasMany(PhienTap::class, 'hoi_vien_id', 'id');
    }

    public function suDungQuyenLois(): HasMany
    {
        return $this->hasMany(SuDungQuyenLoi::class, 'hoi_vien_id', 'id');
    }

    public function tinNhans(): HasMany
    {
        return $this->hasMany(TinNhan::class, 'hoi_vien_id', 'id');
    }

    public function yeuCauTroLys(): HasMany
    {
        return $this->hasMany(YeuCauTroLy::class, 'hoi_vien_id', 'id');
    }
}
