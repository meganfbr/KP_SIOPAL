<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add fields to inventories table
        Schema::table('inventories', function (Blueprint $table) {
            $table->string('kode_pc', 50)->nullable()->unique()->after('kode_inventaris');
            $table->string('no_pc', 50)->nullable()->after('kode_pc');
            $table->foreignId('lokasi_id')->nullable()->after('laboratorium_id')->constrained('laboratoria')->onDelete('set null');
            $table->foreignId('asal_id')->nullable()->after('lokasi_id')->constrained('laboratoria')->onDelete('set null');
            $table->foreignId('petugas_id')->nullable()->after('asal_id')->constrained('users')->onDelete('set null');
        });

        // 2. Create pc_components table
        Schema::create('pc_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->onDelete('cascade');
            $table->string('komponen'); // e.g. Motherboard, Processor, RAM, VGA, Penyimpanan, DVD, Keyboard, Mouse, Monitor
            $table->string('hardware_category'); // e.g. motherboard, processor, ram, vga, penyimpanan, dvd, keyboard, mouse, monitor
            $table->unsignedBigInteger('hardware_id')->nullable(); // ID reference in target table (e.g. motherboards, processors, etc.)
            $table->string('merk_snapshot')->nullable();
            $table->text('detail_snapshot')->nullable();
            $table->string('kondisi')->default('Baik'); // e.g. Baik, Rusak Ringan, Rusak Berat
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pc_components');

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropForeign(['lokasi_id']);
            $table->dropForeign(['asal_id']);
            $table->dropForeign(['petugas_id']);
            $table->dropColumn(['kode_pc', 'no_pc', 'lokasi_id', 'asal_id', 'petugas_id']);
        });
    }
};
