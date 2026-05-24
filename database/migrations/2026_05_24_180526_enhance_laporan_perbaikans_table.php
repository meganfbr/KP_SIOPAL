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
        Schema::table('laporan_perbaikans', function (Blueprint $table) {
            $table->foreignId('inventory_id')->nullable()->after('rekap_inventaris_pc_id')->constrained('inventories')->nullOnDelete();
            $table->foreignId('periode_id')->nullable()->after('inventory_id')->constrained('rekap_inventaris_periodes')->nullOnDelete();
            $table->string('komponen')->nullable()->after('periode_id');
            $table->string('kondisi')->nullable()->after('komponen');
        });

        // Modify status to varchar and set default to 'Menunggu'
        DB::statement("ALTER TABLE laporan_perbaikans MODIFY status VARCHAR(50) DEFAULT 'Menunggu'");
    }

    public function down(): void
    {
        Schema::table('laporan_perbaikans', function (Blueprint $table) {
            $table->dropForeign(['inventory_id']);
            $table->dropColumn('inventory_id');
            $table->dropForeign(['periode_id']);
            $table->dropColumn('periode_id');
            $table->dropColumn(['komponen', 'kondisi']);
        });

        DB::statement("ALTER TABLE laporan_perbaikans MODIFY status ENUM('Pending', 'Diproses', 'Selesai') DEFAULT 'Pending'");
    }
};
