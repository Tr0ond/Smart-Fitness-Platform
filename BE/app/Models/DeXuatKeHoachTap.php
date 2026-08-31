<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeXuatKeHoachTap extends Model
{
    protected $table = 'de_xuat_ke_hoach_tap';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'nguon_de_xuat',
        'nguoi_tao_id',
        'phan_cong_huan_luyen_vien_id',
        'yeu_cau_tro_ly_id',
        'ke_hoach_tap_id',
        'phien_ban_co_so_id',
        'moc_thay_doi_ke_hoach_co_so',
        'phien_ban_ho_so_co_so',
        'loai_thay_doi',
        'tieu_de',
        'giai_thich',
        'noi_dung_de_xuat',
        'phien_ban_cau_truc',
        'ma_bam_noi_dung',
        'ap_dung_tu_ngay',
        'trang_thai',
        'het_han_luc',
        'nguoi_quyet_dinh_id',
        'quyet_dinh_luc',
        'ap_dung_luc',
        'ly_do_ket_thuc',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'nguoi_tao_id' => 'integer',
        'phan_cong_huan_luyen_vien_id' => 'integer',
        'yeu_cau_tro_ly_id' => 'integer',
        'ke_hoach_tap_id' => 'integer',
        'phien_ban_co_so_id' => 'integer',
        'moc_thay_doi_ke_hoach_co_so' => 'integer',
        'phien_ban_ho_so_co_so' => 'integer',
        'noi_dung_de_xuat' => 'array',
        'ap_dung_tu_ngay' => 'date',
        'het_han_luc' => 'datetime',
        'nguoi_quyet_dinh_id' => 'integer',
        'quyet_dinh_luc' => 'datetime',
        'ap_dung_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id', 'id');
    }

    public function phanCongHuanLuyenVien(): BelongsTo
    {
        return $this->belongsTo(PhanCongHuanLuyenVien::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }

    public function yeuCauTroLy(): BelongsTo
    {
        return $this->belongsTo(YeuCauTroLy::class, 'yeu_cau_tro_ly_id', 'id');
    }

    public function keHoachTap(): BelongsTo
    {
        return $this->belongsTo(KeHoachTap::class, 'ke_hoach_tap_id', 'id');
    }

    public function phienBanCoSo(): BelongsTo
    {
        return $this->belongsTo(PhienBanKeHoachTap::class, 'phien_ban_co_so_id', 'id');
    }

    public function nguoiQuyetDinh(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_quyet_dinh_id', 'id');
    }

    public function phienBanKeHoachTap(): HasOne
    {
        return $this->hasOne(PhienBanKeHoachTap::class, 'de_xuat_ke_hoach_tap_id', 'id');
    }
}
