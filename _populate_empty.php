<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Instantiate the page to get access to its protected methods (or just duplicate the logic)
$page = new class extends \App\Filament\Pages\RekapInventaris {
    public function runPopulate() {
        $periodes = \App\Models\RekapInventarisPeriode::all();
        foreach ($periodes as $p) {
            $count = \App\Models\RekapInventarisPc::where('rekap_inventaris_periode_id', $p->id)->count();
            if ($count === 0 && $p->laboratorium_id) {
                echo "Populating periode {$p->nama_periode} (Lab ID: {$p->laboratorium_id})...\n";
                $this->autoPopulateFromMaster($p->id, $p->laboratorium_id);
            }
        }
    }
};

$page->runPopulate();
echo "Done.\n";
