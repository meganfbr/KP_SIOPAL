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
        Schema::create('laporan_perbaikan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_perbaikan_id')
                  ->constrained('laporan_perbaikans')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();
            $table->string('action'); // create, update, status_change, delete
            $table->string('field_changed')->nullable(); // e.g. status, prioritas, keterangan
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('description'); // human-readable log message
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporan_perbaikan_logs');
    }
};
