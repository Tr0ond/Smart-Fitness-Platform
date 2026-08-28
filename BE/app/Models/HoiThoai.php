<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HoSoHoiVien;
use App\Models\HoSoHuanLuyenVien;
use App\Models\PhanCongHuanLuyenVien;
use App\Models\TinNhan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HoiThoai extends Model
{
    protected $table = 'hoi_thoai';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'huan_luyen_vien_id',
        'phan_cong_huan_luyen_vien_id',
        'so_thu_tu_cuoi',
        'hoi_vien_doc_den_so',
        'huan_luyen_vien_doc_den_so',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'huan_luyen_vien_id' => 'integer',
        'phan_cong_huan_luyen_vien_id' => 'integer',
        'so_thu_tu_cuoi' => 'integer',
        'hoi_vien_doc_den_so' => 'integer',
        'huan_luyen_vien_doc_den_so' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function huanLuyenVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHuanLuyenVien::class, 'huan_luyen_vien_id', 'id');
    }

    public function phanCongHuanLuyenVien(): BelongsTo
    {
        return $this->belongsTo(PhanCongHuanLuyenVien::class, 'phan_cong_huan_luyen_vien_id', 'id');
    }

    public function tinNhans(): HasMany
    {
        return $this->hasMany(TinNhan::class, 'hoi_thoai_id', 'id');
    }
}
