<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\LaporanPerbaikan;
use App\Models\LaporanPerbaikanLog;

echo "=== DEBUG LAPORAN 2 ===" . PHP_EOL;
$laporan = LaporanPerbaikan::with('logs')->find(2);

if (!$laporan) {
    echo "Laporan ID 2 tidak ditemukan." . PHP_EOL;
    exit;
}

echo "Laporan ID: {$laporan->id}, No PC: {$laporan->no_pc}, Status: {$laporan->status}" . PHP_EOL;
echo "Logs via relasi: " . $laporan->logs->count() . PHP_EOL;
foreach ($laporan->logs as $log) {
    echo "  - [{$log->action}] field={$log->field_changed} old={$log->old_value} new={$log->new_value}" . PHP_EOL;
    echo "    desc: {$log->description}" . PHP_EOL;
    echo "    by user_id: {$log->user_id}, at: {$log->created_at}" . PHP_EOL;
}

// Check if user relationship works
echo PHP_EOL . "=== CEK RELASI USER PADA LOG ===" . PHP_EOL;
$log = LaporanPerbaikanLog::with('user')->first();
if ($log) {
    echo "Log ID: {$log->id}, User: " . ($log->user ? $log->user->name : 'NULL') . PHP_EOL;
}

// Check the model relation definition
echo PHP_EOL . "=== CEK FILLABLE LOG MODEL ===" . PHP_EOL;
$logModel = new LaporanPerbaikanLog();
echo "Fillable: " . implode(', ', $logModel->getFillable()) . PHP_EOL;
echo "Table: " . $logModel->getTable() . PHP_EOL;
echo "Timestamps: " . ($logModel->timestamps ? 'true' : 'false') . PHP_EOL;
