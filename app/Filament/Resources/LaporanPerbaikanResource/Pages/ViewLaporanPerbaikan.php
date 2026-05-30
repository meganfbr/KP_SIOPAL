<?php

namespace App\Filament\Resources\LaporanPerbaikanResource\Pages;

use App\Filament\Resources\LaporanPerbaikanResource;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\FontWeight;

class ViewLaporanPerbaikan extends ViewRecord
{
    protected static string $resource = LaporanPerbaikanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => auth()->user()->hasRole('super_admin')),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ── Informasi Utama ──
                Section::make('Informasi Laporan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('ruang_lab')->label('Laboratorium'),
                        TextEntry::make('no_pc')->label('Nomor PC'),
                        TextEntry::make('kode_pc')->label('Kode PC')->default('-'),
                        TextEntry::make('komponen')->label('Komponen'),
                        TextEntry::make('kondisi')->label('Kondisi')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'Rusak'       => 'danger',
                                'Kurang Baik' => 'warning',
                                'Tidak Ada'   => 'gray',
                                default       => 'success',
                            }),
                        TextEntry::make('keterangan')->label('Keterangan Kerusakan')->columnSpanFull(),
                    ]),

                Section::make('Status & Penanganan')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('prioritas')->label('Prioritas')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'Rendah' => 'success',
                                'Sedang' => 'warning',
                                'Tinggi' => 'danger',
                                default  => 'gray',
                            }),
                        TextEntry::make('status')->label('Status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'Menunggu' => 'gray',
                                'Diproses' => 'warning',
                                'Selesai'  => 'success',
                                'Ditolak'  => 'danger',
                                default    => 'gray',
                            }),
                        TextEntry::make('tanggal_pengajuan')->label('Tanggal Pengajuan')->date('d M Y'),
                        TextEntry::make('user.name')->label('Pelapor'),
                    ]),

                // ── Riwayat Aktivitas ──
                Section::make('Riwayat Aktivitas')
                    ->icon('heroicon-o-clock')
                    // FIX 1: Hapus persistCollapsed() — section selalu terbuka
                    ->collapsible()
                    ->schema([
                        RepeatableEntry::make('logs')
                            ->label('')
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Waktu')
                                    ->dateTime('d M Y, H:i')
                                    ->weight(FontWeight::SemiBold),

                                TextEntry::make('user.name')
                                    ->label('Oleh')
                                    ->default('—'),

                                TextEntry::make('action')
                                    ->label('Aksi')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        'create'        => 'Dibuat',
                                        'update'        => 'Diperbarui',
                                        'status_change' => 'Status Berubah',
                                        'delete'        => 'Dihapus',
                                        default         => $state,
                                    })
                                    ->color(fn ($state) => match($state) {
                                        'create'        => 'success',
                                        'update'        => 'info',
                                        'status_change' => 'warning',
                                        'delete'        => 'danger',
                                        default         => 'gray',
                                    }),

                                TextEntry::make('description')
                                    ->label('Keterangan')
                                    ->columnSpanFull(),

                                // FIX 2: Gunakan formatStateUsing untuk tampilkan perubahan nilai
                                // tanpa visible() yang tidak reliable di dalam RepeatableEntry
                                TextEntry::make('field_info')
                                    ->label('Perubahan')
                                    ->columnSpanFull()
                                    ->getStateUsing(fn ($record) =>
                                        filled($record->old_value) || filled($record->new_value)
                                            ? ($record->old_value ?? '—') . ' → ' . ($record->new_value ?? '—')
                                            : null
                                    )
                                    ->default(null)
                                    ->hidden(fn ($record) => !filled($record->old_value) && !filled($record->new_value)),
                            ])
                            ->columns(3)
                            ->contained(false),
                    ]),
            ]);
    }
}
