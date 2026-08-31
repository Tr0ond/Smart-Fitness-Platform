<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LanGoiMoHinh extends Model
{
    protected $table = 'lan_goi_mo_hinh';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'yeu_cau_tro_ly_id',
        'so_lan',
        'nha_cung_cap',
        'ten_mo_hinh',
        'ma_yeu_cau_nha_cung_cap',
        'phien_ban_mau_lenh',
        'phien_ban_cau_truc',
        'ma_bam_phan_hoi',
        'ket_qua_cau_truc',
        'ket_qua_kiem_tra',
        'so_don_vi_dau_vao',
        'so_don_vi_dau_ra',
        'trang_thai',
        'bat_dau_luc',
        'ket_thuc_luc',
        'ma_loi',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'yeu_cau_tro_ly_id' => 'integer',
        'so_lan' => 'integer',
        'ket_qua_cau_truc' => 'array',
        'ket_qua_kiem_tra' => 'array',
        'so_don_vi_dau_vao' => 'integer',
        'so_don_vi_dau_ra' => 'integer',
        'bat_dau_luc' => 'datetime',
        'ket_thuc_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function yeuCauTroLy(): BelongsTo
    {
        return $this->belongsTo(YeuCauTroLy::class, 'yeu_cau_tro_ly_id', 'id');
    }
}
