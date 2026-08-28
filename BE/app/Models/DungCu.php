<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaiTapDungCu;
use App\Models\DungCuHoiVien;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DungCu extends Model
{
    protected $table = 'dung_cu';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_dung_cu',
        'ten_dung_cu',
        'mo_ta',
        'trang_thai',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function baiTapDungCus(): HasMany
    {
        return $this->hasMany(BaiTapDungCu::class, 'dung_cu_id', 'id');
    }

    public function dungCuHoiViens(): HasMany
    {
        return $this->hasMany(DungCuHoiVien::class, 'dung_cu_id', 'id');
    }
}
