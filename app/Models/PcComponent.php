<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PcComponent extends Model
{
    use HasFactory;

    protected $table = 'pc_components';

    protected $fillable = [
        'inventory_id',
        'komponen',
        'hardware_category',
        'hardware_id',
        'merk_snapshot',
        'detail_snapshot',
        'kondisi',
        'keterangan',
    ];

    /**
     * Relasi ke model Inventory (PC)
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
