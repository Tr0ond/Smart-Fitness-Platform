<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuKienThanhToan extends Model
{
    protected $table = 'su_kien_thanh_toan';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'lan_thanh_toan_id',
        'ma_kenh_thanh_toan',
        'ma_don_cong_thanh_toan',
        'ma_lien_ket_thanh_toan',
        'ma_tham_chieu',
        'so_tien',
        'don_vi_tien',
        'ma_ket_qua',
        'chu_ky_hop_le',
        'khoa_chong_lap',
        'ma_bam_noi_dung',
        'du_lieu_da_loc',
        'trang_thai_xu_ly',
        'so_lan_nhan',
        'nhan_dau_luc',
        'nhan_cuoi_luc',
        'xu_ly_luc',
        'ly_do',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'lan_thanh_toan_id' => 'integer',
        'ma_don_cong_thanh_toan' => 'integer',
        'so_tien' => 'decimal:0',
        'chu_ky_hop_le' => 'boolean',
        'du_lieu_da_loc' => 'array',
        'so_lan_nhan' => 'integer',
        'nhan_dau_luc' => 'datetime',
        'nhan_cuoi_luc' => 'datetime',
        'xu_ly_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function lanThanhToan(): BelongsTo
    {
        return $this->belongsTo(LanThanhToan::class, 'lan_thanh_toan_id', 'id');
    }
}
