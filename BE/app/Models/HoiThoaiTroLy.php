<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HoiThoaiTroLy extends Model
{
    protected $table = 'hoi_thoai_tro_ly';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'hoi_vien_id',
        'tieu_de',
        'trang_thai',
        'so_thu_tu_cuoi',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_vien_id' => 'integer',
        'so_thu_tu_cuoi' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function hoiVien(): BelongsTo
    {
        return $this->belongsTo(HoSoHoiVien::class, 'hoi_vien_id', 'id');
    }

    public function tinNhanTroLys(): HasMany
    {
        return $this->hasMany(TinNhanTroLy::class, 'hoi_thoai_tro_ly_id', 'id');
    }

    public function yeuCauTroLys(): HasMany
    {
        return $this->hasMany(YeuCauTroLy::class, 'hoi_thoai_tro_ly_id', 'id');
    }
}
