<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class RekapInventarisPc extends Model
{
    use LogsActivity;

    protected $table = 'rekap_inventaris_pcs';

    protected $fillable = [
        'rekap_inventaris_periode_id',
        'rekap_inventaris_spec_id',
        'inventory_id',
        'no_pc',
        'lokasi',
        'kondisi',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['no_pc', 'lokasi', 'kondisi', 'rekap_inventaris_spec_id', 'inventory_id'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Rekap PC telah di-{$eventName}")
            ->useLogName('rekap-inventaris');
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(RekapInventarisPeriode::class, 'rekap_inventaris_periode_id');
    }

    public function spec(): BelongsTo
    {
        return $this->belongsTo(RekapInventarisSpec::class, 'rekap_inventaris_spec_id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}