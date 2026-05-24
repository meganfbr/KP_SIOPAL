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
        // Add inventory_id to rekap_inventaris_pcs and change kondisi to string
        Schema::table('rekap_inventaris_pcs', function (Blueprint $table) {
            $table->foreignId('inventory_id')->nullable()->after('rekap_inventaris_spec_id')->constrained('inventories')->nullOnDelete();
            // MySQL needs to change enum to string using doctrine/dbal or raw SQL. Since we use string, let's just make it a string.
            // But modifying enum to string in Laravel sometimes has issues if doctrine/dbal is missing. Let's use string.
        });

        // Use raw SQL to alter the enum columns safely to string (varchar 50)
        DB::statement("ALTER TABLE rekap_inventaris_pcs MODIFY kondisi VARCHAR(50) DEFAULT 'Baik'");
        DB::statement("ALTER TABLE rekap_inventaris_spec_details MODIFY kondisi VARCHAR(50) DEFAULT 'Baik'");
    }

    public function down(): void
    {
        Schema::table('rekap_inventaris_pcs', function (Blueprint $table) {
            $table->dropForeign(['inventory_id']);
            $table->dropColumn('inventory_id');
        });
        
        DB::statement("ALTER TABLE rekap_inventaris_pcs MODIFY kondisi ENUM('Baik', 'Rusak') DEFAULT 'Baik'");
        DB::statement("ALTER TABLE rekap_inventaris_spec_details MODIFY kondisi ENUM('Baik', 'Kurang Baik', 'Rusak') DEFAULT 'Baik'");
    }
};
