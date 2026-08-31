<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GiaoAnMau extends Model
{
    protected $table = 'giao_an_mau';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $dateFormat = 'Y-m-d H:i:s.u';

    public $timestamps = true;

    public const CREATED_AT = 'ngay_tao';

    public const UPDATED_AT = 'ngay_cap_nhat';

    protected $fillable = [
        'ma_giao_an',
        'ten_giao_an',
        'mo_ta',
        'muc_tieu',
        'trinh_do',
        'so_buoi_moi_tuan',
        'phien_ban_noi_dung',
        'trang_thai',
        'nguoi_tao_id',
        'ngay_tao',
        'ngay_cap_nhat',
    ];

    protected $casts = [
        'id' => 'integer',
        'so_buoi_moi_tuan' => 'integer',
        'phien_ban_noi_dung' => 'integer',
        'nguoi_tao_id' => 'integer',
        'ngay_tao' => 'datetime',
        'ngay_cap_nhat' => 'datetime',
    ];

    public function nguoiTao(): BelongsTo
    {
        return $this->belongsTo(NguoiDung::class, 'nguoi_tao_id', 'id');
    }

    public function giaoAnUngViens(): HasMany
    {
        return $this->hasMany(GiaoAnUngVien::class, 'giao_an_mau_id', 'id');
    }

    public function ngayTrongGiaoAns(): HasMany
    {
        return $this->hasMany(NgayTrongGiaoAn::class, 'giao_an_mau_id', 'id');
    }

    public function phienBanKeHoachTaps(): HasMany
    {
        return $this->hasMany(PhienBanKeHoachTap::class, 'giao_an_mau_id', 'id');
    }
}
