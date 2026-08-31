<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuyenLoiGoiTap extends Model
{
    protected $table = 'quyen_loi_goi_tap';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'goi_tap_id',
        'cho_phep_vao_phong_tap',
        'cho_phep_tro_ly_tap_luyen',
        'gioi_han_luot_tro_ly',
        'cho_phep_tro_chuyen_huan_luyen_vien',
        'so_buoi_huan_luyen_vien',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'goi_tap_id' => 'integer',
        'cho_phep_vao_phong_tap' => 'boolean',
        'cho_phep_tro_ly_tap_luyen' => 'boolean',
        'gioi_han_luot_tro_ly' => 'integer',
        'cho_phep_tro_chuyen_huan_luyen_vien' => 'boolean',
        'so_buoi_huan_luyen_vien' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function goiTap(): BelongsTo
    {
        return $this->belongsTo(GoiTap::class, 'goi_tap_id', 'id');
    }
}
