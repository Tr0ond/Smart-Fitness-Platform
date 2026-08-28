<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaiTapTrongPhien;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HiepTap extends Model
{
    protected $table = 'hiep_tap';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'bai_tap_trong_phien_id',
        'so_thu_tu',
        'ma_hiep_thuc_hien',
        'so_lan_lap',
        'khoi_luong_kg',
        'thoi_gian_nghi_thuc_te_giay',
        'hoan_thanh_luc',
        'phien_ban_du_lieu',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'bai_tap_trong_phien_id' => 'integer',
        'so_thu_tu' => 'integer',
        'so_lan_lap' => 'integer',
        'khoi_luong_kg' => 'decimal:2',
        'thoi_gian_nghi_thuc_te_giay' => 'integer',
        'hoan_thanh_luc' => 'datetime',
        'phien_ban_du_lieu' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function baiTapTrongPhien(): BelongsTo
    {
        return $this->belongsTo(BaiTapTrongPhien::class, 'bai_tap_trong_phien_id', 'id');
    }
}
