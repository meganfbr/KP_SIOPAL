<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KlasifikasiLab;
use App\Models\Laboratorium;

class LaboratoriumSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Klasifikasi Lab
        $kategoriDasar = KlasifikasiLab::firstOrCreate(
            ['kode_kategori' => 'K01'],
            ['nama_kategori' => 'Laboratorium Komputer Dasar']
        );
        $kategoriMenengah = KlasifikasiLab::firstOrCreate(
            ['kode_kategori' => 'K02'],
            ['nama_kategori' => 'Laboratorium Komputer Menengah']
        );
        $kategoriLanjut = KlasifikasiLab::firstOrCreate(
            ['kode_kategori' => 'K03'],
            ['nama_kategori' => 'Laboratorium Komputer Lanjut']
        );

        // 2. Buat Laboratorium
        $labs = [
            ['ruang' => 'LAB D2A', 'kapasitas' => 40, 'kategori_id' => $kategoriDasar->id],
            ['ruang' => 'LAB D2B', 'kapasitas' => 40, 'kategori_id' => $kategoriDasar->id],
            ['ruang' => 'LAB D2C', 'kapasitas' => 40, 'kategori_id' => $kategoriDasar->id],
            ['ruang' => 'LAB D2D', 'kapasitas' => 40, 'kategori_id' => $kategoriDasar->id],
            ['ruang' => 'LAB D2E', 'kapasitas' => 40, 'kategori_id' => $kategoriMenengah->id],
            ['ruang' => 'LAB D2F', 'kapasitas' => 40, 'kategori_id' => $kategoriMenengah->id],
            ['ruang' => 'LAB D2G', 'kapasitas' => 40, 'kategori_id' => $kategoriMenengah->id],
            ['ruang' => 'LAB D2H', 'kapasitas' => 40, 'kategori_id' => $kategoriMenengah->id],
            ['ruang' => 'LAB D2I', 'kapasitas' => 40, 'kategori_id' => $kategoriLanjut->id],
            ['ruang' => 'LAB D2J', 'kapasitas' => 40, 'kategori_id' => $kategoriLanjut->id],
            ['ruang' => 'LAB D2K', 'kapasitas' => 40, 'kategori_id' => $kategoriLanjut->id],
            ['ruang' => 'LAB D3L', 'kapasitas' => 40, 'kategori_id' => $kategoriLanjut->id],
            ['ruang' => 'LAB D3M', 'kapasitas' => 40, 'kategori_id' => $kategoriLanjut->id],
            ['ruang' => 'LAB D3N', 'kapasitas' => 40, 'kategori_id' => $kategoriLanjut->id],
        ];

        foreach ($labs as $labData) {
            Laboratorium::firstOrCreate(
                ['ruang' => $labData['ruang']],
                [
                    'kapasitas' => $labData['kapasitas'],
                    'pc_siap' => $labData['kapasitas'],
                    'pc_backup' => 2,
                    'kategori_id' => $labData['kategori_id'],
                    'keterangan' => 'Laboratorium ' . $labData['ruang'],
                ]
            );
        }

        $this->command->info('✅ Tabel Klasifikasi Lab dan Laboratorium berhasil diisi!');
    }
}
