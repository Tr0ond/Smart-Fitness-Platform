<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TheTruyCap extends Model
{
    protected $table = 'the_truy_cap';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'nguoi_dung_id',
        'ten_thiet_bi',
        'ma_bam_the',
        'pham_vi_truy_cap',
        'su_dung_gan_nhat_luc',
        'het_han_luc',
        'thu_hoi_luc',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'nguoi_dung_id' => 'integer',
        'pham_vi_truy_cap' => 'array',
        'su_dung_gan_nhat_luc' => 'datetime',
        'het_han_luc' => 'datetime',
        'thu_hoi_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id', 'id');
    }
}
