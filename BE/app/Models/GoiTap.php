<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ChiNhanh;
use App\Models\DonMuaGoi;
use App\Models\NguoiDung;
use App\Models\QuyenLoiGoiTap;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GoiTap extends Model
{
    protected $table = 'goi_tap';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'chi_nhanh_id',
        'ma_goi',
        'ten_goi',
        'gia',
        'thoi_han_ngay',
        'mo_ta',
        'trang_thai',
        'phien_ban_cau_hinh',
        'nguoi_tao_id',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'chi_nhanh_id' => 'integer',
        'gia' => 'decimal:0',
        'thoi_han_ngay' => 'integer',
        'phien_ban_cau_hinh' => 'integer',
        'nguoi_tao_id' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function chiNhanh(): BelongsTo
    {
        return $this->belongsTo(ChiNhanh::class, 'chi_nhanh_id', 'id');
    }

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id', 'id');
    }

    public function donMuaGois(): HasMany
    {
        return $this->hasMany(DonMuaGoi::class, 'goi_tap_id', 'id');
    }

    public function quyenLoiGoiTap(): HasOne
    {
        return $this->hasOne(QuyenLoiGoiTap::class, 'goi_tap_id', 'id');
    }
}
