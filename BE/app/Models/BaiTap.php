<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BaiTap extends Model
{
    protected $table = 'bai_tap';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_bai_tap',
        'ten_bai_tap',
        'do_kho',
        'huong_dan',
        'duong_dan_hinh_anh',
        'duong_dan_video',
        'thong_tin_bo_sung',
        'phien_ban_noi_dung',
        'trang_thai',
        'nguoi_tao_id',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'thong_tin_bo_sung' => 'array',
        'phien_ban_noi_dung' => 'integer',
        'nguoi_tao_id' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id', 'id');
    }

    public function baiTapDungCus(): HasMany
    {
        return $this->hasMany(BaiTapDungCu::class, 'bai_tap_id', 'id');
    }

    public function baiTapNhomCos(): HasMany
    {
        return $this->hasMany(BaiTapNhomCo::class, 'bai_tap_id', 'id');
    }

    public function baiTapTrongGiaoAns(): HasMany
    {
        return $this->hasMany(BaiTapTrongGiaoAn::class, 'bai_tap_id', 'id');
    }

    public function baiTapTrongKeHoachs(): HasMany
    {
        return $this->hasMany(BaiTapTrongKeHoach::class, 'bai_tap_id', 'id');
    }

    public function baiTapTrongPhiens(): HasMany
    {
        return $this->hasMany(BaiTapTrongPhien::class, 'bai_tap_id', 'id');
    }

    public function baiTapUngViens(): HasMany
    {
        return $this->hasMany(BaiTapUngVien::class, 'bai_tap_id', 'id');
    }
}
