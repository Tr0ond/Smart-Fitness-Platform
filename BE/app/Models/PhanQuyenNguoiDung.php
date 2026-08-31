<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhanQuyenNguoiDung extends Model
{
    protected $table = 'phan_quyen_nguoi_dung';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'nguoi_dung_id',
        'vai_tro_id',
        'nguoi_cap_id',
        'cap_luc',
        'thu_hoi_luc',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'nguoi_dung_id' => 'integer',
        'vai_tro_id' => 'integer',
        'nguoi_cap_id' => 'integer',
        'cap_luc' => 'datetime',
        'thu_hoi_luc' => 'datetime',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function nguoiDung(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_dung_id', 'id');
    }

    public function vaiTro(): BelongsTo
    {
        return $this->belongsTo(VaiTro::class, 'vai_tro_id', 'id');
    }

    public function nguoiCap(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_cap_id', 'id');
    }
}
