<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaiTap;
use App\Models\YeuCauTroLy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BaiTapUngVien extends Model
{
    protected $table = 'bai_tap_ung_vien';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = false;

    protected $fillable = [
        'yeu_cau_tro_ly_id',
        'bai_tap_id',
        'phien_ban_noi_dung',
        'du_lieu_da_chot',
        'ly_do_phu_hop',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'yeu_cau_tro_ly_id' => 'integer',
        'bai_tap_id' => 'integer',
        'phien_ban_noi_dung' => 'integer',
        'du_lieu_da_chot' => 'array',
        'ngay_tao' => 'datetime',
    ];

    public function yeuCauTroLy(): BelongsTo
    {
        return $this->belongsTo(YeuCauTroLy::class, 'yeu_cau_tro_ly_id', 'id');
    }

    public function baiTap(): BelongsTo
    {
        return $this->belongsTo(BaiTap::class, 'bai_tap_id', 'id');
    }
}
