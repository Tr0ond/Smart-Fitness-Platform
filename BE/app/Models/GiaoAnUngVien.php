<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiaoAnUngVien extends Model
{
    protected $table = 'giao_an_ung_vien';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = false;

    protected $fillable = [
        'yeu_cau_tro_ly_id',
        'giao_an_mau_id',
        'phien_ban_noi_dung',
        'du_lieu_da_chot',
        'ly_do_phu_hop',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'yeu_cau_tro_ly_id' => 'integer',
        'giao_an_mau_id' => 'integer',
        'phien_ban_noi_dung' => 'integer',
        'du_lieu_da_chot' => 'array',
        'ngay_tao' => 'datetime',
    ];

    public function yeuCauTroLy(): BelongsTo
    {
        return $this->belongsTo(YeuCauTroLy::class, 'yeu_cau_tro_ly_id', 'id');
    }

    public function giaoAnMau(): BelongsTo
    {
        return $this->belongsTo(GiaoAnMau::class, 'giao_an_mau_id', 'id');
    }
}
