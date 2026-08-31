<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YeuCauDatLaiMatKhau extends Model
{
    protected $table = 'yeu_cau_dat_lai_mat_khau';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'nguoi_dung_id',
        'ma_bam_xac_nhan',
        'het_han_luc',
        'da_su_dung_luc',
        'thu_hoi_luc',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'nguoi_dung_id' => 'integer',
        'het_han_luc' => 'datetime',
        'da_su_dung_luc' => 'datetime',
        'thu_hoi_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id', 'id');
    }
}
