<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DonMuaGoi extends Model
{
    protected $table = 'don_mua_goi';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'goi_tap_id',
        'ma_don',
        'ma_yeu_cau',
        'so_tien_phai_thu',
        'don_vi_tien',
        'trang_thai',
        'chot_gia_luc',
        'het_han_thanh_toan_luc',
        'thanh_toan_luc',
        'huy_luc',
        'ly_do_huy',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'goi_tap_id' => 'integer',
        'so_tien_phai_thu' => 'decimal:0',
        'chot_gia_luc' => 'datetime',
        'het_han_thanh_toan_luc' => 'datetime',
        'thanh_toan_luc' => 'datetime',
        'huy_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function goiTap(): BelongsTo
    {
        return $this->belongsTo(GoiTap::class, 'goi_tap_id', 'id');
    }

    public function kyHanHoiVien(): HasOne
    {
        return $this->hasOne(KyHanHoiVien::class, 'don_mua_goi_id', 'id');
    }

    public function lanThanhToans(): HasMany
    {
        return $this->hasMany(LanThanhToan::class, 'don_mua_goi_id', 'id');
    }
}
