<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DangKyGoiTap extends Model
{
    protected $table = 'dang_ky_goi_tap';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'chi_nhanh_id',
        'trang_thai',
        'lan_su_dung_dau_tien_id',
        'ngay_bat_dau',
        'ket_thuc_ghi_nhan_luc',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'chi_nhanh_id' => 'integer',
        'lan_su_dung_dau_tien_id' => 'integer',
        'ngay_bat_dau' => 'datetime',
        'ket_thuc_ghi_nhan_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function chiNhanh(): BelongsTo
    {
        return $this->belongsTo(ChiNhanh::class, 'chi_nhanh_id', 'id');
    }

    public function lanSuDungDauTien(): BelongsTo
    {
        return $this->belongsTo(SuDungQuyenLoi::class, 'lan_su_dung_dau_tien_id', 'id');
    }

    public function kyHanHoiViens(): HasMany
    {
        return $this->hasMany(KyHanHoiVien::class, 'dang_ky_goi_tap_id', 'id');
    }
}
