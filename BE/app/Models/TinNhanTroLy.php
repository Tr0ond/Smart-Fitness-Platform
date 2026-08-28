<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HoiThoaiTroLy;
use App\Models\YeuCauTroLy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TinNhanTroLy extends Model
{
    protected $table = 'tin_nhan_tro_ly';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';
    protected $dateFormat = 'Y-m-d H:i:s.u';
    public $timestamps = false;

    protected $fillable = [
        'hoi_thoai_tro_ly_id',
        'so_thu_tu',
        'nguon_tin',
        'ma_tin_nhan_phia_gui',
        'yeu_cau_tro_ly_id',
        'noi_dung',
        'gui_luc',
        'ngay_tao',
    ];

    protected $casts = [
        'id' => 'integer',
        'hoi_thoai_tro_ly_id' => 'integer',
        'so_thu_tu' => 'integer',
        'yeu_cau_tro_ly_id' => 'integer',
        'gui_luc' => 'datetime',
        'ngay_tao' => 'datetime',
    ];

    public function hoiThoaiTroLy(): BelongsTo
    {
        return $this->belongsTo(HoiThoaiTroLy::class, 'hoi_thoai_tro_ly_id', 'id');
    }
    public function yeuCauTroLy(): BelongsTo
    {
        return $this->belongsTo(YeuCauTroLy::class, 'yeu_cau_tro_ly_id', 'id');
    }

    public function yeuCauTroLyTheoTinNhanDauVao(): HasOne
    {
        return $this->hasOne(YeuCauTroLy::class, 'tin_nhan_dau_vao_id', 'id');
    }
}
