@php
    $logs = $getState();
@endphp

@if ($logs && $logs->count() > 0)
    <div class="relative space-y-6 before:absolute before:inset-y-0 before:left-[11px] before:w-px before:bg-gray-200 dark:before:bg-white/10 ml-2">
        @foreach ($logs as $log)
            <div class="relative pl-8">
                <!-- Dot marker -->
                <div class="absolute left-0 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-white ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-white/10">
                    <div class="h-2 w-2 rounded-full @if($log->action == 'create') bg-success-500 @elseif($log->action == 'update' || $log->action == 'status_change') bg-warning-500 @else bg-gray-500 @endif"></div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <!-- Header: User and Action -->
                    <div class="flex items-center gap-2 text-sm flex-wrap">
                        <span class="font-semibold text-gray-950 dark:text-white">
                            {{ $log->user?->name ?? 'Sistem' }}
                        </span>
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ match($log->action) {
                                'create' => 'membuat laporan',
                                'update' => 'memperbarui laporan',
                                'status_change' => 'mengubah status/prioritas',
                                'delete' => 'menghapus data',
                                default => $log->action,
                            } }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500 ml-auto">
                            {{ $log->created_at->diffForHumans() }} ({{ $log->created_at->format('d M Y, H:i') }})
                        </span>
                    </div>

                    <!-- Description -->
                    @if ($log->description)
                        <div class="text-sm text-gray-600 dark:text-gray-300 mt-1 bg-gray-50 dark:bg-white/5 p-3 rounded-lg ring-1 ring-gray-200 dark:ring-white/10">
                            {{ $log->description }}
                        </div>
                    @endif

                    <!-- Field Changes -->
                    @if ($log->old_value || $log->new_value)
                        <div class="flex items-center gap-2 mt-1 text-sm bg-primary-50 dark:bg-primary-500/10 p-2.5 rounded-lg ring-1 ring-primary-500/20">
                            <span class="font-medium text-gray-700 dark:text-gray-300 capitalize">{{ str_replace('_', ' ', $log->field_changed) }}:</span>
                            <span class="text-danger-600 dark:text-danger-400 line-through">{{ $log->old_value ?? '—' }}</span>
                            <x-heroicon-m-arrow-right class="h-4 w-4 text-gray-400" />
                            <span class="text-success-600 dark:text-success-400 font-medium">{{ $log->new_value ?? '—' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-sm text-gray-500 italic">Belum ada riwayat aktivitas.</div>
@endif
