<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Laboratorium;
use App\Models\PCDetail;
use App\Models\PcComponent;
use App\Models\Motherboard;
use App\Models\Processor;
use App\Models\RAM;
use App\Models\Penyimpanan;
use App\Models\VGA;
use App\Models\PSU;
use App\Models\Keyboard;
use App\Models\Mouse;
use App\Models\Monitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PCInventoryRevisionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_kode_pc_dan_no_pc_digenerate_otomatis_saat_membuat_inventory_pc_baru()
    {
        // 1. Get seeded lab and components
        $lab = Laboratorium::first();
        
        $pcDetail = PCDetail::create([
            'no_inventaris' => 'PCDETAIL/TEST/001',
            'motherboard_id' => Motherboard::first()->id,
            'processor_id' => Processor::first()->id,
            'ram_id' => RAM::first()->id,
            'penyimpanan_id' => Penyimpanan::first()->id,
            'vga_id' => VGA::first()->id,
            'psu_id' => PSU::first()->id,
            'keyboard_id' => Keyboard::first()->id,
            'mouse_id' => Mouse::first()->id,
            'monitor_id' => Monitor::first()->id,
        ]);

        $inventory = new Inventory();
        $inventory->laboratorium_id = $lab->id;
        $inventory->inventoriable_type = PCDetail::class;
        $inventory->inventoriable_id = $pcDetail->id;
        $inventory->kondisi = 'Baik';
        $inventory->save();

        // 2. Assertions
        $inventory->refresh();
        $this->assertEquals('0001', $inventory->kode_pc);
        
        $labCode = strtoupper(str_replace(['LAB ', ' '], '', $lab->ruang));
        $this->assertEquals("{$labCode}/001", $inventory->no_pc);
        $this->assertEquals($lab->id, $inventory->lokasi_id);
        $this->assertNull($inventory->asal_id);
    }

    public function test_asal_id_mencatat_lokasi_lama_dan_no_pc_digenerate_ulang_saat_pc_dipindahkan_lab()
    {
        // 1. Get labs
        $labs = Laboratorium::take(2)->get();
        if ($labs->count() < 2) {
            $this->markTestSkipped('Not enough laboratories seeded for this test.');
        }

        $lab1 = $labs->first();
        $lab2 = $labs->last();

        $pcDetail = PCDetail::create([
            'no_inventaris' => 'PCDETAIL/TEST/002',
            'motherboard_id' => Motherboard::first()->id,
            'processor_id' => Processor::first()->id,
            'ram_id' => RAM::first()->id,
            'penyimpanan_id' => Penyimpanan::first()->id,
            'vga_id' => VGA::first()->id,
            'psu_id' => PSU::first()->id,
            'keyboard_id' => Keyboard::first()->id,
            'mouse_id' => Mouse::first()->id,
            'monitor_id' => Monitor::first()->id,
        ]);

        $inventory = new Inventory();
        $inventory->laboratorium_id = $lab1->id;
        $inventory->inventoriable_type = PCDetail::class;
        $inventory->inventoriable_id = $pcDetail->id;
        $inventory->kondisi = 'Baik';
        $inventory->save();

        $this->assertEquals('0001', $inventory->kode_pc);
        
        $lab1Code = strtoupper(str_replace(['LAB ', ' '], '', $lab1->ruang));
        $this->assertEquals("{$lab1Code}/001", $inventory->no_pc);

        // 2. Pindahkan lab
        $inventory->laboratorium_id = $lab2->id;
        $inventory->save();

        // 3. Assertions
        $inventory->refresh();
        $this->assertEquals('0001', $inventory->kode_pc); // Kode PC tetap
        $this->assertEquals($lab1->id, $inventory->asal_id); // Asal ID berisi lab lama
        $this->assertEquals($lab2->id, $inventory->lokasi_id); // Lokasi ID berisi lab baru
        
        $lab2Code = strtoupper(str_replace(['LAB ', ' '], '', $lab2->ruang));
        $this->assertEquals("{$lab2Code}/001", $inventory->no_pc); // No PC digenerate ulang
    }

    public function test_sync_pc_components_dapat_mensinkronisasikan_detail_komponen_ke_tabel_pc_components()
    {
        // 1. Get lab and hardware
        $lab = Laboratorium::first();
        $motherboard = Motherboard::first();
        $processor = Processor::first();
        $ram = RAM::first();
        $penyimpanan = Penyimpanan::first();
        $vga = VGA::first();
        $psu = PSU::first();
        $keyboard = Keyboard::first();
        $mouse = Mouse::first();
        $monitor = Monitor::first();

        $pcDetail = PCDetail::create([
            'no_inventaris' => 'PCDETAIL/TEST/003',
            'motherboard_id' => $motherboard->id,
            'processor_id' => $processor->id,
            'ram_id' => $ram->id,
            'penyimpanan_id' => $penyimpanan->id,
            'vga_id' => $vga->id,
            'psu_id' => $psu->id,
            'keyboard_id' => $keyboard->id,
            'mouse_id' => $mouse->id,
            'monitor_id' => $monitor->id,
        ]);

        $inventory = new Inventory();
        $inventory->laboratorium_id = $lab->id;
        $inventory->inventoriable_type = PCDetail::class;
        $inventory->inventoriable_id = $pcDetail->id;
        $inventory->kondisi = 'Baik';
        $inventory->save();

        // Sync components
        $inventory->syncPcComponents($pcDetail->toArray());

        // 2. Assertions
        $comp = PcComponent::where('inventory_id', $inventory->id)->where('hardware_category', 'processor')->first();
        $this->assertNotNull($comp);
        $this->assertEquals('Processor', $comp->komponen);
        $this->assertEquals($processor->id, $comp->hardware_id);
        $this->assertEquals($processor->merk, $comp->merk_snapshot);
        $this->assertEquals($processor->full_name, $comp->detail_snapshot);
    }
}
