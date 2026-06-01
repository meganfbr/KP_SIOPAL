<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\KlasifikasiLab;
use App\Models\Laboratorium;

class GudangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buat klasifikasi khusus untuk Gudang jika belum ada
        $kategoriGudang = KlasifikasiLab::firstOrCreate(
            ['kode_kategori' => 'K99'],
            ['nama_kategori' => 'Gudang Inventaris']
        );

        // Buat record Gudang
        Laboratorium::updateOrCreate(
            ['ruang' => 'Gudang'],
            [
                'kapasitas' => 999, // kapasitas tak terbatas/besar
                'pc_siap' => 0,
                'pc_backup' => 0,
                'kategori_id' => $kategoriGudang->id,
                'keterangan' => 'Gudang Pusat Penyimpanan Inventaris',
                'is_active' => true,
            ]
        );

        $this->command->info('✅ Lokasi Gudang berhasil ditambahkan ke database!');
    }
}
