<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4 text-sm pb-4 border-b border-gray-200 dark:border-gray-700">
        <div>
            <span class="text-gray-500 dark:text-gray-400">Kode PC:</span>
            <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ $record->kode_pc ?? '-' }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">No PC:</span>
            <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ $record->no_pc ?? '-' }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Lokasi:</span>
            <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ $record->lokasi->ruang ?? ($record->laboratorium->ruang ?? '-') }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Kondisi Umum:</span>
            <span class="font-semibold ml-1">
                @php
                    $kondisiColor = match($record->kondisi) {
                        'Baik' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400',
                        'Rusak Ringan' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400',
                        'Rusak Berat' => 'bg-rose-500/10 text-rose-700 dark:text-rose-400',
                        default => 'bg-sky-500/10 text-sky-700 dark:text-sky-400',
                    };
                @endphp
                <span class="px-2 py-0.5 text-xs rounded-full {{ $kondisiColor }}">
                    {{ $record->kondisi }}
                </span>
            </span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Asal PC:</span>
            <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ $record->asal->ruang ?? 'Pengadaan Baru' }}</span>
        </div>
        <div>
            <span class="text-gray-500 dark:text-gray-400">Petugas:</span>
            <span class="font-semibold text-gray-900 dark:text-white ml-1">{{ $record->petugas->name ?? '-' }}</span>
        </div>
    </div>

    <div>
        <h4 class="text-sm font-bold text-gray-900 dark:text-white mb-3">Detail Komponen Hardware</h4>
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-sm text-gray-500 dark:text-gray-400">
                <thead class="bg-gray-50 dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-300 uppercase font-semibold">
                    <tr>
                        <th class="px-4 py-3">Komponen</th>
                        <th class="px-4 py-3">Merk / Model</th>
                        <th class="px-4 py-3">Kondisi</th>
                        <th class="px-4 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
                    @forelse ($record->pcComponents as $comp)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $comp->komponen }}</td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-gray-700 dark:text-gray-200">{{ $comp->merk_snapshot ?? '-' }}</span>
                                @if($comp->detail_snapshot)
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $comp->detail_snapshot }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeColor = match($comp->kondisi) {
                                        'Baik' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-400',
                                        'Rusak Ringan' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/30 dark:text-amber-400',
                                        'Rusak Berat' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/30 dark:text-rose-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $badgeColor }}">
                                    {{ $comp->kondisi }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs italic">{{ $comp->keterangan ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-4 text-center text-gray-400">Belum ada komponen hardware yang terpasang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
