<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaiTapTrongGiaoAn;
use App\Models\GiaoAnMau;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NgayTrongGiaoAn extends Model
{
    protected $table = 'ngay_trong_giao_an';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'giao_an_mau_id',
        'so_thu_tu',
        'ten_ngay',
        'thoi_luong_du_kien_phut',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'giao_an_mau_id' => 'integer',
        'so_thu_tu' => 'integer',
        'thoi_luong_du_kien_phut' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function giaoAnMau(): BelongsTo
    {
        return $this->belongsTo(GiaoAnMau::class, 'giao_an_mau_id', 'id');
    }

    public function baiTapTrongGiaoAns(): HasMany
    {
        return $this->hasMany(BaiTapTrongGiaoAn::class, 'ngay_trong_giao_an_id', 'id');
    }
}
