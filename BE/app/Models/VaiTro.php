<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PhanQuyenNguoiDung;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VaiTro extends Model
{
    protected $table = 'vai_tro';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = true;
    public const CREATED_AT = 'ngay_tao';
    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_vai_tro',
        'ten_vai_tro',
        'mo_ta',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function phanQuyenNguoiDungs(): HasMany
    {
        return $this->hasMany(PhanQuyenNguoiDung::class, 'vai_tro_id', 'id');
    }
}
