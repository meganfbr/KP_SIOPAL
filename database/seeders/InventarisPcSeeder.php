<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Laboratorium;
use App\Models\PCDetail;
use App\Models\User;
use App\Models\Motherboard;
use App\Models\Processor;
use App\Models\RAM;
use App\Models\VGA;
use App\Models\Penyimpanan;
use App\Models\PSU;
use App\Models\Keyboard;
use App\Models\Mouse;
use App\Models\Monitor;
use App\Models\DVD;
use App\Models\Headphone;
use Illuminate\Database\Seeder;

class InventarisPcSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labs = ['D2A', 'D2B', 'D2C', 'D2D', 'D2E', 'D2F', 'D2G', 'D2H', 'D2I', 'D2J', 'D2K', 'D3L', 'D3M', 'D3N'];
        
        // Fetch all components IDs to pick random ones
        $motherboards = Motherboard::pluck('id')->toArray();
        $processors = Processor::pluck('id')->toArray();
        $rams = RAM::pluck('id')->toArray();
        $vgas = VGA::pluck('id')->toArray();
        $penyimpanans = Penyimpanan::pluck('id')->toArray();
        $psus = PSU::pluck('id')->toArray();
        $keyboards = Keyboard::pluck('id')->toArray();
        $mice = Mouse::pluck('id')->toArray();
        $monitors = Monitor::pluck('id')->toArray();
        $dvds = DVD::pluck('id')->toArray();
        $headphones = Headphone::pluck('id')->toArray();

        // Make sure we have master components
        if (empty($processors) || empty($motherboards)) {
            $this->command->error('Master data hardware kosong. Silakan jalankan php artisan db:seed terlebih dahulu.');
            return;
        }

        $overallIndex = 1;

        foreach ($labs as $labCode) {
            // Find laboratorium
            $labName = 'LAB ' . $labCode;
            $laboratorium = Laboratorium::where('ruang', $labName)
                ->orWhere('ruang', $labCode)
                ->first();

            if (!$laboratorium) {
                $this->command->warn("Laboratorium {$labName} tidak ditemukan. Skip.");
                continue;
            }

            // Find laboran user for this lab
            $email = 'laboran_lab' . strtolower($labCode) . '@mail.com';
            $petugas = User::where('email', $email)->first();
            $petugasId = $petugas ? $petugas->id : null;

            $this->command->info("Seeding PC untuk Laboratorium {$labName}...");

            for ($i = 1; $i <= 50; $i++) {
                $kodePc = str_pad($overallIndex, 4, '0', STR_PAD_LEFT);
                $noPc = "{$labCode}/" . str_pad($i, 3, '0', STR_PAD_LEFT);

                $pcDetailData = [
                    'motherboard_id' => $motherboards[array_rand($motherboards)],
                    'processor_id' => $processors[array_rand($processors)],
                    'ram_id' => $rams[array_rand($rams)],
                    'penyimpanan_id' => $penyimpanans[array_rand($penyimpanans)],
                    'vga_id' => $vgas[array_rand($vgas)],
                    'psu_id' => $psus[array_rand($psus)],
                    'keyboard_id' => $keyboards[array_rand($keyboards)],
                    'mouse_id' => $mice[array_rand($mice)],
                    'monitor_id' => $monitors[array_rand($monitors)],
                    'dvd_id' => !empty($dvds) && rand(0, 1) ? $dvds[array_rand($dvds)] : null,
                    'headphone_id' => !empty($headphones) && rand(0, 1) ? $headphones[array_rand($headphones)] : null,
                ];

                // Safely update or create PCDetail based on unique no_inventaris
                $noInventarisDetail = "PCDETAIL/{$kodePc}/" . date('Y');
                $pcDetail = PCDetail::where('no_inventaris', $noInventarisDetail)->first();
                if ($pcDetail) {
                    $pcDetail->update($pcDetailData);
                } else {
                    $pcDetail = PCDetail::create(array_merge([
                        'no_inventaris' => $noInventarisDetail,
                    ], $pcDetailData));
                }

                // Format kode_inventaris: UDN/LABKOM/INV/namalab/PC01
                $namaLabUpper = strtoupper(str_replace(' ', '', $laboratorium->ruang));
                $nomorUrut = str_pad($i, 2, '0', STR_PAD_LEFT);
                $kodeInventaris = "UDN/LABKOM/INV/{$namaLabUpper}/PC{$nomorUrut}";

                // Update or create Inventory record based on unique kode_pc
                $inventory = Inventory::updateOrCreate(
                    ['kode_pc' => $kodePc],
                    [
                        'laboratorium_id' => $laboratorium->id,
                        'lokasi_id' => $laboratorium->id,
                        'inventoriable_type' => PCDetail::class,
                        'inventoriable_id' => $pcDetail->id,
                        'no_pc' => $noPc,
                        'kondisi' => 'Baik',
                        'petugas_id' => $petugasId,
                        'kode_inventaris' => $kodeInventaris,
                    ]
                );

                // Sync component details to pc_components snapshot table
                $inventory->syncPcComponents($pcDetailData);

                $overallIndex++;
            }
        }

        $this->command->info("Berhasil men-seed " . ($overallIndex - 1) . " data Inventaris PC.");
    }
}
