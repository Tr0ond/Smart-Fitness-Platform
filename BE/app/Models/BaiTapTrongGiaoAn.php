<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BaiTapTrongGiaoAn extends Model
{
    protected $table = 'bai_tap_trong_giao_an';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ngay_trong_giao_an_id',
        'bai_tap_id',
        'so_thu_tu',
        'so_hiep_muc_tieu',
        'so_lan_lap_toi_thieu',
        'so_lan_lap_toi_da',
        'thoi_gian_nghi_giay',
        'ghi_chu',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'ngay_trong_giao_an_id' => 'integer',
        'bai_tap_id' => 'integer',
        'so_thu_tu' => 'integer',
        'so_hiep_muc_tieu' => 'integer',
        'so_lan_lap_toi_thieu' => 'integer',
        'so_lan_lap_toi_da' => 'integer',
        'thoi_gian_nghi_giay' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function ngayTrongGiaoAn(): BelongsTo
    {
        return $this->belongsTo(NgayTrongGiaoAn::class, 'ngay_trong_giao_an_id', 'id');
    }

    public function baiTap(): BelongsTo
    {
        return $this->belongsTo(BaiTap::class, 'bai_tap_id', 'id');
    }
}
