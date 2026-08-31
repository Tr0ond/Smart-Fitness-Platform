<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuKienPhatTinNhan extends Model
{
    protected $table = 'su_kien_phat_tin_nhan';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'tin_nhan_id',
        'trang_thai',
        'so_lan_thu',
        'thu_lai_luc',
        'phat_luc',
        'loi_gan_nhat',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'tin_nhan_id' => 'integer',
        'so_lan_thu' => 'integer',
        'thu_lai_luc' => 'datetime',
        'phat_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function tinNhan(): BelongsTo
    {
        return $this->belongsTo(TinNhan::class, 'tin_nhan_id', 'id');
    }
}
