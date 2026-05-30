<?php

namespace App\Observers;

use App\Models\LaporanPerbaikan;
use App\Models\LaporanPerbaikanLog;
use Illuminate\Support\Facades\Auth;

class LaporanPerbaikanObserver
{
    /**
     * Handle the LaporanPerbaikan "created" event.
     */
    public function created(LaporanPerbaikan $laporan): void
    {
        $user = Auth::user();
        if (!$user) return;

        $nopc = $laporan->no_pc ?? '-';
        $lab  = $laporan->ruang_lab ?? '-';
        $komponen = $laporan->komponen ?? '-';

        LaporanPerbaikanLog::create([
            'laporan_perbaikan_id' => $laporan->id,
            'user_id'              => $user->id,
            'action'               => 'create',
            'field_changed'        => null,
            'old_value'            => null,
            'new_value'            => null,
            'description'          => "{$user->name} membuat laporan pengajuan perbaikan untuk PC {$nopc} ({$komponen}) di {$lab}.",
            'created_at'           => now(),
        ]);
    }

    /**
     * Handle the LaporanPerbaikan "updated" event.
     */
    public function updated(LaporanPerbaikan $laporan): void
    {
        $user = Auth::user();
        if (!$user) return;

        $trackedFields = [
            'status'     => 'Status laporan',
            'prioritas'  => 'Prioritas laporan',
            'keterangan' => 'Keterangan kerusakan',
        ];

        foreach ($trackedFields as $field => $label) {
            if (!$laporan->wasChanged($field)) continue;

            $oldVal = $laporan->getOriginal($field) ?? '-';
            $newVal = $laporan->$field ?? '-';

            $action = $field === 'status' ? 'status_change' : 'update';

            $description = match($field) {
                'status'     => "{$user->name} mengubah status laporan PC {$laporan->no_pc}: {$oldVal} → {$newVal}.",
                'prioritas'  => "{$user->name} mengubah prioritas laporan PC {$laporan->no_pc}: {$oldVal} → {$newVal}.",
                'keterangan' => "{$user->name} memperbarui keterangan kerusakan pada laporan PC {$laporan->no_pc}.",
                default      => "{$user->name} mengubah {$label} laporan PC {$laporan->no_pc}.",
            };

            LaporanPerbaikanLog::create([
                'laporan_perbaikan_id' => $laporan->id,
                'user_id'              => $user->id,
                'action'               => $action,
                'field_changed'        => $field,
                'old_value'            => $oldVal,
                'new_value'            => $newVal,
                'description'          => $description,
                'created_at'           => now(),
            ]);
        }
    }

    /**
     * Handle the LaporanPerbaikan "deleted" event.
     */
    public function deleted(LaporanPerbaikan $laporan): void
    {
        $user = Auth::user();
        if (!$user) return;

        // Logs have cascadeOnDelete so they'll be removed. We log to activity_logs instead
        // via the generic ActivityLog mechanism if needed. No-op here to avoid writing to
        // an already-deleted related row.
    }
}
