<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\PCDetail;
use App\Models\PcComponent;
use Illuminate\Database\Seeder;

class PcComponentSeeder extends Seeder
{
    /**
     * Seed detail komponen PC berdasarkan data Inventaris PC yang sudah ada.
     *
     * Seeder ini membaca setiap Inventory bertipe PCDetail, lalu mengambil
     * data komponen hardware dari relasi pc_details dan menyinkronkan
     * snapshot-nya ke tabel pc_components menggunakan updateOrCreate.
     *
     * Aman dijalankan berulang kali — tidak akan membuat duplikasi.
     *
     * Jalankan: php artisan db:seed --class=PcComponentSeeder
     */
    public function run(): void
    {
        // Ambil semua Inventaris PC beserta relasi inventoriable (PCDetail)
        $inventories = Inventory::where('inventoriable_type', PCDetail::class)
            ->with('inventoriable')
            ->get();

        if ($inventories->isEmpty()) {
            $this->command->warn('Tidak ada data Inventaris PC ditemukan. Jalankan InventarisPcSeeder terlebih dahulu.');
            return;
        }

        $this->command->info("Memproses {$inventories->count()} data Inventaris PC...");

        $synced = 0;
        $skipped = 0;

        foreach ($inventories as $inventory) {
            /** @var PCDetail|null $pcDetail */
            $pcDetail = $inventory->inventoriable;

            if (!$pcDetail) {
                $skipped++;
                continue;
            }

            // Ambil array komponen dari PCDetail untuk disinkronkan
            $componentData = [
                'motherboard_id'  => $pcDetail->motherboard_id,
                'processor_id'    => $pcDetail->processor_id,
                'ram_id'          => $pcDetail->ram_id,
                'penyimpanan_id'  => $pcDetail->penyimpanan_id,
                'vga_id'          => $pcDetail->vga_id,
                'psu_id'          => $pcDetail->psu_id,
                'keyboard_id'     => $pcDetail->keyboard_id,
                'mouse_id'        => $pcDetail->mouse_id,
                'monitor_id'      => $pcDetail->monitor_id,
                'dvd_id'          => $pcDetail->dvd_id,          // nullable / optional
                'headphone_id'    => $pcDetail->headphone_id,    // nullable / optional
            ];

            // Gunakan method syncPcComponents() yang sudah ada di model Inventory
            // Method ini menggunakan updateOrCreate secara internal
            $inventory->syncPcComponents($componentData);

            $synced++;
        }

        $this->command->info("✅ Berhasil sinkronisasi komponen untuk {$synced} PC.");
        if ($skipped > 0) {
            $this->command->warn("⚠️  {$skipped} Inventaris PC dilewati (PCDetail tidak ditemukan).");
        }

        // Tampilkan ringkasan jumlah record
        $totalComponents = PcComponent::count();
        $this->command->info("📊 Total record di tabel pc_components: {$totalComponents}");
    }
}
