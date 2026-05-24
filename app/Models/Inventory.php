<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Traits\HasActivityLog;

class Inventory extends Model
{
    use HasFactory, LogsActivity, HasActivityLog;

    protected $activityModul = 'Inventaris';

    protected $guarded = ['id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Inventaris telah di-{$eventName}")
            ->useLogName('inventaris');
    }

    /**
     * Relasi polimorfik untuk mendapatkan model detail (PCDetail, NonPCDetail, dll).
     */
    public function inventoriable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relasi ke model Laboratorium.
     */
    public function laboratorium(): BelongsTo
    {
        return $this->belongsTo(Laboratorium::class);
    }

    /**
     * Relasi ke lokasi laboratorium saat ini.
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Laboratorium::class, 'lokasi_id');
    }

    /**
     * Relasi ke lokasi laboratorium asal/sebelumnya.
     */
    public function asal(): BelongsTo
    {
        return $this->belongsTo(Laboratorium::class, 'asal_id');
    }

    /**
     * Relasi ke petugas terkait (User).
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    /**
     * Relasi ke komponen PC (PcComponent).
     */
    public function pcComponents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PcComponent::class, 'inventory_id');
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        // Temporarily commenting out the global scope to debug 500 error
        /*
        static::addGlobalScope('lab-permissions', function (Builder $builder) {
            // Skip scope for console commands or when no user is authenticated
            if (app()->runningInConsole() || !Auth::check()) {
                return;
            }

            $user = Auth::user();

            // Super admin can see all inventory, no filtering needed
            if ($user->hasRole('super_admin')) {
                return;
            }

            // For all other users, only show inventory items from labs they have permission to access
            $authorizedLabIds = $user->getAuthorizedLabIds('view');
            $builder->whereIn('laboratorium_id', $authorizedLabIds);
        });
        */

        // Auto-generate nomor inventaris sebelum menyimpan
        static::creating(function ($inventory) {
            // Ambil nama laboratorium
            $laboratorium = Laboratorium::find($inventory->laboratorium_id);
            $namaLab = $laboratorium ? strtoupper($laboratorium->ruang) : 'LAB';

            // Helper function to get last number from kode_inventaris
            $getLastNumber = function ($query) {
                $last = $query->orderByRaw("CAST(SUBSTRING_INDEX(kode_inventaris, '/', -1) AS UNSIGNED) DESC")
                    ->first();

                if ($last && $last->kode_inventaris) {
                    $parts = explode('/', $last->kode_inventaris);
                    return (int) end($parts);
                }
                return 0;
            };

            // Generate nomor inventaris untuk PCDetail
            if ($inventory->inventoriable_type === 'App\Models\PCDetail') {
                if (empty($inventory->kode_inventaris)) {
                    $lastNumber = $getLastNumber(
                        self::where('laboratorium_id', $inventory->laboratorium_id)
                            ->where('inventoriable_type', 'App\Models\PCDetail')
                            ->whereNotNull('kode_inventaris')
                    );

                    $nomorUrut = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);

                    // Format: UDN/LABKOM/INV/namalab/PC01
                    $inventory->kode_inventaris = "UDN/LABKOM/INV/{$namaLab}/PC{$nomorUrut}";
                }

                // Auto-generate kode_pc: 4 digit display (e.g. 0001, 0002)
                if (empty($inventory->kode_pc)) {
                    $maxKodePc = (int) self::where('inventoriable_type', 'App\Models\PCDetail')->max('kode_pc');
                    $inventory->kode_pc = str_pad($maxKodePc + 1, 4, '0', STR_PAD_LEFT);
                }

                // Sync lokasi_id with laboratorium_id
                if (empty($inventory->lokasi_id)) {
                    $inventory->lokasi_id = $inventory->laboratorium_id;
                }

                // Auto-generate no_pc: {lab_code}/{3_digit_urut}
                if (empty($inventory->no_pc) && $laboratorium) {
                    $labCode = strtoupper(str_replace(['LAB ', ' '], '', $laboratorium->ruang));
                    $activeCount = self::where('inventoriable_type', 'App\Models\PCDetail')
                        ->where('laboratorium_id', $inventory->laboratorium_id)
                        ->count();
                    $nomorUrutPosisi = str_pad($activeCount + 1, 3, '0', STR_PAD_LEFT);
                    $inventory->no_pc = "{$labCode}/{$nomorUrutPosisi}";
                }
            }

            // Generate nomor inventaris untuk NonPCDetail
            if ($inventory->inventoriable_type === 'App\Models\NonPCDetail') {
                if (empty($inventory->kode_inventaris)) {
                    $lastNumber = $getLastNumber(
                        self::where('laboratorium_id', $inventory->laboratorium_id)
                            ->where('inventoriable_type', 'App\Models\NonPCDetail')
                            ->whereNotNull('kode_inventaris')
                    );

                    $nomorUrut = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);

                    // Format: UDN/LABKOM/INV/NON-PC/namalab/01
                    $inventory->kode_inventaris = "UDN/LABKOM/INV/NON-PC/{$namaLab}/{$nomorUrut}";
                }
            }

            // Generate nomor inventaris untuk SoftwareDetail
            if ($inventory->inventoriable_type === 'App\Models\SoftwareDetail') {
                if (empty($inventory->kode_inventaris)) {
                    $lastNumber = $getLastNumber(
                        self::where('laboratorium_id', $inventory->laboratorium_id)
                            ->where('inventoriable_type', 'App\Models\SoftwareDetail')
                            ->whereNotNull('kode_inventaris')
                    );

                    $nomorUrut = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);

                    // Format: UDN/LABKOM/INV/SOFTWARE/namalab/01
                    $inventory->kode_inventaris = "UDN/LABKOM/INV/SOFTWARE/{$namaLab}/{$nomorUrut}";
                }
            }
        });

        static::updating(function ($inventory) {
            // Jangan ubah nomor inventaris saat update
            if ($inventory->isDirty('kode_inventaris') && $inventory->getOriginal('kode_inventaris')) {
                $inventory->kode_inventaris = $inventory->getOriginal('kode_inventaris');
            }

            if ($inventory->inventoriable_type === 'App\Models\PCDetail') {
                // If laboratorium_id or lokasi_id changes
                if ($inventory->isDirty('laboratorium_id') || $inventory->isDirty('lokasi_id')) {
                    $oldLabId = $inventory->getOriginal('laboratorium_id') ?? $inventory->getOriginal('lokasi_id');
                    $newLabId = $inventory->isDirty('laboratorium_id') ? $inventory->laboratorium_id : $inventory->lokasi_id;

                    $inventory->laboratorium_id = $newLabId;
                    $inventory->lokasi_id = $newLabId;
                    $inventory->asal_id = $oldLabId;

                    // Re-generate no_pc
                    $lab = Laboratorium::find($newLabId);
                    if ($lab) {
                        $labCode = strtoupper(str_replace(['LAB ', ' '], '', $lab->ruang));
                        $activeCount = self::where('inventoriable_type', 'App\Models\PCDetail')
                            ->where('laboratorium_id', $newLabId)
                            ->count();
                        $nomorUrutPosisi = str_pad($activeCount + 1, 3, '0', STR_PAD_LEFT);
                        $inventory->no_pc = "{$labCode}/{$nomorUrutPosisi}";
                    }
                }
            }
        });
    }

    /**
     * Sinkronisasi komponen PC ke tabel pc_components.
     */
    public function syncPcComponents(array $detailsData): void
    {
        $mappings = [
            'processor_id' => [
                'komponen' => 'Processor',
                'category' => 'processor',
                'model' => \App\Models\Processor::class,
            ],
            'motherboard_id' => [
                'komponen' => 'Motherboard',
                'category' => 'motherboard',
                'model' => \App\Models\Motherboard::class,
            ],
            'ram_id' => [
                'komponen' => 'RAM',
                'category' => 'ram',
                'model' => \App\Models\RAM::class,
            ],
            'penyimpanan_id' => [
                'komponen' => 'Penyimpanan',
                'category' => 'penyimpanan',
                'model' => \App\Models\Penyimpanan::class,
            ],
            'vga_id' => [
                'komponen' => 'VGA',
                'category' => 'vga',
                'model' => \App\Models\VGA::class,
            ],
            'psu_id' => [
                'komponen' => 'PSU',
                'category' => 'psu',
                'model' => \App\Models\PSU::class,
            ],
            'keyboard_id' => [
                'komponen' => 'Keyboard',
                'category' => 'keyboard',
                'model' => \App\Models\Keyboard::class,
            ],
            'mouse_id' => [
                'komponen' => 'Mouse',
                'category' => 'mouse',
                'model' => \App\Models\Mouse::class,
            ],
            'monitor_id' => [
                'komponen' => 'Monitor',
                'category' => 'monitor',
                'model' => \App\Models\Monitor::class,
            ],
            'dvd_id' => [
                'komponen' => 'DVD',
                'category' => 'dvd',
                'model' => \App\Models\DVD::class,
            ],
            'headphone_id' => [
                'komponen' => 'Headphone',
                'category' => 'headphone',
                'model' => \App\Models\Headphone::class,
            ],
        ];

        foreach ($mappings as $key => $config) {
            $hardwareId = $detailsData[$key] ?? null;

            if ($hardwareId) {
                $componentModel = $config['model'];
                $componentRecord = $componentModel::find($hardwareId);

                if ($componentRecord) {
                    \App\Models\PcComponent::updateOrCreate(
                        [
                            'inventory_id' => $this->id,
                            'hardware_category' => $config['category'],
                        ],
                        [
                            'komponen' => $config['komponen'],
                            'hardware_id' => $hardwareId,
                            'merk_snapshot' => $componentRecord->merk ?? null,
                            'detail_snapshot' => $componentRecord->full_name ?? ($componentRecord->tipe ?? null),
                        ]
                    );
                }
            } else {
                \App\Models\PcComponent::where('inventory_id', $this->id)
                    ->where('hardware_category', $config['category'])
                    ->delete();
            }
        }
    }
}
