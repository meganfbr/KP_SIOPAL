<?php
 
namespace Database\Seeders;
 
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Laboratorium;
use App\Models\Inventory;
use App\Models\PCDetail;
use App\Models\RekapInventarisPeriode;
use App\Models\RekapInventarisSpec;
use App\Models\RekapInventarisSpecDetail;
use App\Models\RekapInventarisPc;
use App\Models\RekapInventarisNonPc;
use App\Services\RekapInventarisSpecService;
 
class JanuariRekapInventarisSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
 
            // 1. Dapatkan atau buat data lab D2A
            $labD2A = Laboratorium::where('ruang', 'LAB D2A')->orWhere('ruang', 'D2A')->first();
            $laboratoriumId = $labD2A ? $labD2A->id : 1;
 
            // 2. Buat periode rekap
            $periode = RekapInventarisPeriode::firstOrCreate(
                [
                    'laboratorium_id' => $laboratoriumId,
                    'bulan' => 1,
                    'tahun' => 2026,
                ],
                [
                    'nama_periode' => 'Januari 2026',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
 
            // Clear old data for this period to ensure idempotency
            RekapInventarisPc::where('rekap_inventaris_periode_id', $periode->id)->delete();
            RekapInventarisNonPc::where('rekap_inventaris_periode_id', $periode->id)->delete();
            RekapInventarisSpecDetail::whereIn(
                'rekap_inventaris_spec_id',
                RekapInventarisSpec::where('rekap_inventaris_periode_id', $periode->id)->pluck('id')
            )->delete();
            RekapInventarisSpec::where('rekap_inventaris_periode_id', $periode->id)->delete();
 
            // 3. Ambil data Inventaris PC dari database
            $pcs = Inventory::where('laboratorium_id', $laboratoriumId)
                ->where('inventoriable_type', PCDetail::class)
                ->with([
                    'inventoriable.motherboard',
                    'inventoriable.processor',
                    'inventoriable.ram',
                    'inventoriable.penyimpanan',
                    'inventoriable.vga',
                    'inventoriable.dvd',
                    'inventoriable.keyboard',
                    'inventoriable.mouse',
                    'inventoriable.monitor'
                ])
                ->orderBy('no_pc', 'asc')
                ->get();
 
            $specService = new RekapInventarisSpecService();
            $totalPcs = $pcs->count();
 
            // 4. Seeding Rekap PC secara dinamis berdasarkan data Inventaris
            foreach ($pcs as $index => $pc) {
                $pcDetail = $pc->inventoriable;
 
                // Susun detail komponen dari PC detail
                $details = [
                    1 => ['detail' => $pcDetail->motherboard?->full_name ?? '-', 'kondisi' => 'Baik', 'catatan_kondisi' => ''],
                    2 => ['detail' => $pcDetail->processor?->full_name ?? '-', 'kondisi' => 'Baik', 'catatan_kondisi' => ''],
                    3 => ['detail' => $pcDetail->penyimpanan?->full_name ?? '-', 'kondisi' => 'Baik', 'catatan_kondisi' => ''],
                    4 => ['detail' => $pcDetail->vga?->full_name ?? '-', 'kondisi' => 'Baik', 'catatan_kondisi' => ''],
                    5 => ['detail' => $pcDetail->ram?->full_name ?? '-', 'kondisi' => 'Baik', 'catatan_kondisi' => ''],
                    6 => ['detail' => $pcDetail->dvd?->full_name ?? '-', 'kondisi' => 'Baik', 'catatan_kondisi' => ''],
                    7 => ['detail' => $pcDetail->keyboard?->full_name ?? '-', 'kondisi' => 'Baik', 'catatan_kondisi' => ''],
                    8 => ['detail' => $pcDetail->mouse?->full_name ?? '-', 'kondisi' => 'Baik', 'catatan_kondisi' => ''],
                    9 => ['detail' => $pcDetail->monitor?->full_name ?? '-', 'kondisi' => 'Baik', 'catatan_kondisi' => ''],
                ];
 
                // Find or create spec fingerprint
                $spec = $specService->findOrCreate($periode->id, $details, $pc->kondisi ?? 'Baik');
 
                // Tentukan lokasi secara dinamis: 2 PC terakhir adalah Laboran dan Dosen, sisanya Client
                $lokasi = 'Client';
                if ($totalPcs >= 2) {
                    if ($index === $totalPcs - 2) {
                        $lokasi = 'Laboran';
                    } elseif ($index === $totalPcs - 1) {
                        $lokasi = 'Dosen';
                    }
                }
 
                RekapInventarisPc::create([
                    'rekap_inventaris_periode_id' => $periode->id,
                    'rekap_inventaris_spec_id' => $spec->id,
                    'inventory_id' => $pc->id,
                    'no_pc' => $pc->no_pc, // Gunakan no_pc dari inventories
                    'lokasi' => $lokasi,
                    'kondisi' => $pc->kondisi ?? 'Baik',
                ]);
            }
 
            // 5. Seed Non-PC
            $nonPcs = [
                ['nama_barang' => 'Papan tulis (whiteboard)', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Proyektor Hitachi', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => 'Pengganti Proyektor SONY'],
                ['nama_barang' => 'Layar proyektor BEST', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Video Switcher ATEN 4-to-1', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'VGA Switch + 2 kabel VGA', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Amplifier TOA ZA-301 & speaker ruangan', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => '1 set'],
                ['nama_barang' => 'Microphone SHURE + kabel', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Speaker Altec Lansing', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Switch TP-Link 8 port Gigabit (Client)', 'jumlah' => 14, 'kondisi' => 'Baik', 'keterangan' => 'Semua Baru Agustus'],
                ['nama_barang' => 'Switch TP-Link 16 port 10/100Mbps', 'jumlah' => 2, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'SSD RX7 NVME', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Kursi Chitose', 'jumlah' => 47, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Mouse Pad Hitam', 'jumlah' => 44, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Mouse Pad Biru', 'jumlah' => 0, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'AC Daikin', 'jumlah' => 2, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Meja PC', 'jumlah' => 47, 'kondisi' => 'Baik', 'keterangan' => '1 kondisi mengenaskan, 1 kondisi ada yang lepas'],
                ['nama_barang' => 'Kabinet laci, 3 tingkat', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Jam dinding', 'jumlah' => 0, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'baterai AA', 'jumlah' => 4, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'baterai AAA', 'jumlah' => 4, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'baterai charger', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Contact Cleaner', 'jumlah' => 0, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'mic wireless sennheiser', 'jumlah' => 2, 'kondisi' => 'Baik', 'keterangan' => '1 Rusak (Jatuh)'],
                ['nama_barang' => 'mic kabel', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Air Purifier', 'jumlah' => 1, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Headset 4TECH', 'jumlah' => 35, 'kondisi' => 'Baik', 'keterangan' => null],
                ['nama_barang' => 'Headset Sades', 'jumlah' => 11, 'kondisi' => 'Baik', 'keterangan' => null],
            ];
 
            foreach ($nonPcs as $nonPc) {
                RekapInventarisNonPc::create(array_merge([
                    'rekap_inventaris_periode_id' => $periode->id,
                ], $nonPc));
            }
 
            // 6. Urutkan kode spec secara teratur
            $specService->syncPeriodSpecOrder($periode->id);
        });
    }
}