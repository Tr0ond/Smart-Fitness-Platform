<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DangKyGoiTap;
use App\Models\GoiTap;
use App\Models\LichSuVaoPhongTap;
use App\Models\MaVaoPhongTap;
use App\Models\NguoiDung;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChiNhanh extends Model
{
    protected $table = 'chi_nhanh';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_chi_nhanh',
        'ten_chi_nhanh',
        'dia_chi',
        'so_dien_thoai',
        'mui_gio',
        'trang_thai',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function dangKyGoiTaps(): HasMany
    {
        return $this->hasMany(DangKyGoiTap::class, 'chi_nhanh_id', 'id');
    }

    public function goiTaps(): HasMany
    {
        return $this->hasMany(GoiTap::class, 'chi_nhanh_id', 'id');
    }

    public function lichSuVaoPhongTaps(): HasMany
    {
        return $this->hasMany(LichSuVaoPhongTap::class, 'chi_nhanh_id', 'id');
    }

    public function maVaoPhongTaps(): HasMany
    {
        return $this->hasMany(MaVaoPhongTap::class, 'chi_nhanh_id', 'id');
    }

    public function nguoiDungs(): HasMany
    {
        return $this->hasMany(NguoiDung::class, 'chi_nhanh_id', 'id');
    }
}
