<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NhatKyHeThong extends Model
{
    protected $table = 'nhat_ky_he_thong';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = false;

    protected $fillable = [
        'nguoi_thuc_hien_id',
        'loai_tac_nhan',
        'hanh_dong',
        'loai_doi_tuong',
        'dinh_danh_doi_tuong',
        'khoa_tuong_quan',
        'du_lieu_truoc',
        'du_lieu_sau',
        'ket_qua',
        'thuc_hien_luc',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'nguoi_thuc_hien_id' => 'integer',
        'dinh_danh_doi_tuong' => 'integer',
        'du_lieu_truoc' => 'array',
        'du_lieu_sau' => 'array',
        'thuc_hien_luc' => 'datetime',
        'ngay_tao' => 'datetime',
    ];

    public function nguoiThucHien(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_thuc_hien_id', 'id');
    }
}
