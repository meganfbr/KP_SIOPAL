<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

use App\Traits\HasActivityLog;

class PCDetail extends Model
{
    use HasFactory, HasActivityLog;

    protected $activityModul = 'Komponen PC';

    protected $table = 'pc_details';
    protected $guarded = ['id'];

    /**
     * Relasi polimorfik balik ke model Inventory.
     */
    public function inventory(): MorphOne
    {
        return $this->morphOne(Inventory::class, 'inventoriable');
    }

    // Definisikan relasi ke setiap model master komponen
    public function processor(): BelongsTo { return $this->belongsTo(Processor::class); }
    public function motherboard(): BelongsTo { return $this->belongsTo(Motherboard::class); }
    public function ram(): BelongsTo { return $this->belongsTo(RAM::class); }
    public function penyimpanan(): BelongsTo { return $this->belongsTo(Penyimpanan::class); }
    public function vga(): BelongsTo { return $this->belongsTo(VGA::class); }
    public function psu(): BelongsTo { return $this->belongsTo(PSU::class); }
    public function keyboard(): BelongsTo { return $this->belongsTo(Keyboard::class); }
    public function mouse(): BelongsTo { return $this->belongsTo(Mouse::class); }
    public function monitor(): BelongsTo { return $this->belongsTo(Monitor::class); }
    public function dvd(): BelongsTo { return $this->belongsTo(DVD::class); }
    public function headphone(): BelongsTo { return $this->belongsTo(Headphone::class); }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pcDetail) {
            if (empty($pcDetail->no_inventaris)) {
                // Generate nomor inventaris untuk PC Detail
                $lastId = self::max('id') + 1;
                $kodeUnik = str_pad($lastId, 3, '0', STR_PAD_LEFT);
                $tahun = date('Y');

                // Format: PCDETAIL/001/2025
                $pcDetail->no_inventaris = "PCDETAIL/{$kodeUnik}/{$tahun}";
            }
        });
    }
}
