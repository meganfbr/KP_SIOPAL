<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LaporanPerbaikan extends Model
{
    use HasFactory, LogsActivity;

    protected $activityModul = 'Laporan Perbaikan';

    protected $table = 'laporan_perbaikans';

    protected $fillable = [
        'rekap_inventaris_pc_id',
        'inventory_id',
        'periode_id',
        'komponen',
        'kondisi',
        'laboratorium_id',
        'no_pc',
        'kode_pc',
        'ruang_lab',
        'prioritas',
        'keterangan',
        'komponen_rusak',
        'status',
        'tanggal_pengajuan',
        'user_id',
    ];

    protected $casts = [
        'komponen_rusak' => 'array',
        'tanggal_pengajuan' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['no_pc', 'ruang_lab', 'prioritas', 'status', 'inventory_id', 'periode_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Laporan Perbaikan telah di-{$eventName}")
            ->useLogName('laporan-perbaikan');
    }

    public function rekapPc(): BelongsTo
    {
        return $this->belongsTo(RekapInventarisPc::class, 'rekap_inventaris_pc_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(RekapInventarisPeriode::class, 'periode_id');
    }

    public function laboratorium(): BelongsTo
    {
        return $this->belongsTo(Laboratorium::class, 'laboratorium_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LaporanPerbaikanLog::class, 'laporan_perbaikan_id')->orderBy('created_at', 'asc');
    }
}
