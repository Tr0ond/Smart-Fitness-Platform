<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MaVaoPhongTap extends Model
{
    protected $table = 'ma_vao_phong_tap';

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
        'ma_bam_bi_mat',
        'phat_hanh_luc',
        'het_han_luc',
        'thu_hoi_luc',
        'da_su_dung_luc',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'chi_nhanh_id' => 'integer',
        'phat_hanh_luc' => 'datetime',
        'het_han_luc' => 'datetime',
        'thu_hoi_luc' => 'datetime',
        'da_su_dung_luc' => 'datetime',
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

    public function lichSuVaoPhongTap(): HasOne
    {
        return $this->hasOne(LichSuVaoPhongTap::class, 'ma_vao_phong_tap_id', 'id');
    }
}
