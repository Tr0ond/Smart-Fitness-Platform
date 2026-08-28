<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BuoiTapDuKien;
use App\Models\DeXuatKeHoachTap;
use App\Models\GiaoAnMau;
use App\Models\KeHoachTap;
use App\Models\NgayTrongKeHoach;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PhienBanKeHoachTap extends Model
{
    protected $table = 'phien_ban_ke_hoach_tap';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = false;

    protected $fillable = [
        'ke_hoach_tap_id',
        'so_phien_ban',
        'phien_ban_truoc_id',
        'giao_an_mau_id',
        'ten_giao_an_da_chon',
        'de_xuat_ke_hoach_tap_id',
        'nguon_tao',
        'nguoi_tao_id',
        'muc_tieu',
        'ap_dung_tu_ngay',
        'ly_do_thay_doi',
        'ma_bam_noi_dung',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'ke_hoach_tap_id' => 'integer',
        'so_phien_ban' => 'integer',
        'phien_ban_truoc_id' => 'integer',
        'giao_an_mau_id' => 'integer',
        'de_xuat_ke_hoach_tap_id' => 'integer',
        'nguoi_tao_id' => 'integer',
        'ap_dung_tu_ngay' => 'date',
        'ngay_tao' => 'datetime',
    ];

    public function keHoachTap(): BelongsTo
    {
        return $this->belongsTo(KeHoachTap::class, 'ke_hoach_tap_id', 'id');
    }
    public function phienBanTruoc(): BelongsTo
    {
        return $this->belongsTo(PhienBanKeHoachTap::class, 'phien_ban_truoc_id', 'id');
    }

    public function giaoAnMau(): BelongsTo
    {
        return $this->belongsTo(GiaoAnMau::class, 'giao_an_mau_id', 'id');
    }

    public function deXuatKeHoachTap(): BelongsTo
    {
        return $this->belongsTo(DeXuatKeHoachTap::class, 'de_xuat_ke_hoach_tap_id', 'id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id', 'id');
    }

    public function buoiTapDuKiens(): HasMany
    {
        return $this->hasMany(BuoiTapDuKien::class, 'phien_ban_ke_hoach_tap_id', 'id');
    }

    public function deXuatKeHoachTaps(): HasMany
    {
        return $this->hasMany(DeXuatKeHoachTap::class, 'phien_ban_co_so_id', 'id');
    }

    public function keHoachHienTai(): HasOne
    {
        return $this->hasOne(KeHoachTap::class, 'phien_ban_hien_tai_id', 'id');
    }

    public function ngayTrongKeHoachs(): HasMany
    {
        return $this->hasMany(NgayTrongKeHoach::class, 'phien_ban_ke_hoach_tap_id', 'id');
    }

    public function phienBanKeHoachTaps(): HasMany
    {
        return $this->hasMany(PhienBanKeHoachTap::class, 'phien_ban_truoc_id', 'id');
    }
}
