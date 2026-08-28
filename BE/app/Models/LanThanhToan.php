<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DonMuaGoi;
use App\Models\KyHanHoiVien;
use App\Models\SuKienThanhToan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LanThanhToan extends Model
{
    protected $table = 'lan_thanh_toan';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'don_mua_goi_id',
        'so_lan',
        'ma_kenh_thanh_toan',
        'ma_don_cong_thanh_toan',
        'ma_lien_ket_thanh_toan',
        'duong_dan_thanh_toan',
        'so_tien_yeu_cau',
        'don_vi_tien',
        'trang_thai',
        'ma_tham_chieu_duoc_chap_nhan',
        'so_tien_da_nhan',
        'thanh_toan_luc',
        'xac_nhan_luc',
        'het_han_luc',
        'ma_loi',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'don_mua_goi_id' => 'integer',
        'so_lan' => 'integer',
        'ma_don_cong_thanh_toan' => 'integer',
        'so_tien_yeu_cau' => 'decimal:0',
        'so_tien_da_nhan' => 'decimal:0',
        'thanh_toan_luc' => 'datetime',
        'xac_nhan_luc' => 'datetime',
        'het_han_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function donMuaGoi(): BelongsTo
    {
        return $this->belongsTo(DonMuaGoi::class, 'don_mua_goi_id', 'id');
    }

    public function kyHanHoiVien(): HasOne
    {
        return $this->hasOne(KyHanHoiVien::class, 'lan_thanh_toan_id', 'id');
    }

    public function suKienThanhToans(): HasMany
    {
        return $this->hasMany(SuKienThanhToan::class, 'lan_thanh_toan_id', 'id');
    }
}
