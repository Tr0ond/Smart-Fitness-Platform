<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChiSoCoThe extends Model
{
    protected $table = 'chi_so_co_the';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = false;

    protected $fillable = [
        'hoi_vien_id',
        'do_luc',
        'can_nang_kg',
        'chieu_cao_cm',
        'vong_eo_cm',
        'ghi_chu',
        'ma_lan_ghi',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'do_luc' => 'datetime',
        'can_nang_kg' => 'decimal:2',
        'chieu_cao_cm' => 'decimal:2',
        'vong_eo_cm' => 'decimal:2',
        'ngay_tao' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }
}
