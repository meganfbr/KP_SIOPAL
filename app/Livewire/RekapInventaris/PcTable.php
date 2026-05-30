<?php

namespace App\Livewire\RekapInventaris;

use App\Models\RekapInventarisPc;
use Filament\Forms\Get;
use Filament\Tables;
use Filament\Tables\Table;
use Livewire\Component;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;

class PcTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public int $periodeId;
    public int $bulan;
    public int $tahun;
    public ?int $laboratoriumId = null;

    public function mount(int $periodeId, int $bulan, int $tahun, ?int $laboratoriumId = null): void
    {
        $this->periodeId = $periodeId;
        $this->bulan = $bulan;
        $this->tahun = $tahun;
        $this->laboratoriumId = $laboratoriumId;
    }

    public function updated($propertyName)
    {
        // Reset form state when needed
        if (str_starts_with($propertyName, 'tableFilters')) {
            $this->resetTable();
        }
    }

    public function resetTable()
    {
        $this->resetPage();
    }

    public function closeModal()
    {
        $this->dispatch('close-modal');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RekapInventarisPc::query()
                    ->where('rekap_inventaris_periode_id', $this->periodeId)
                    ->with('spec.details')
                    ->orderByRaw('CAST(SUBSTRING(no_pc, 2) AS UNSIGNED)')
            )
            ->heading('Rekap PC')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah PC')
                    ->visible(false)
                    ->modalWidth('7xl')
                    ->form($this->getPcFormSchema(isEdit: false))
                    ->using(function (array $data) {
                        $periodeId = $this->periodeId;
                        $service = resolve(\App\Services\RekapInventarisSpecService::class);

                        $details = $data['spec_details'] ?? [];
                        $kondisiList = collect($details)->pluck('kondisi')->filter()->toArray();
                        $calculatedKondisi = 'Baik';
                        if (in_array('Tidak Ada', $kondisiList)) {
                            $calculatedKondisi = 'Tidak Ada';
                        } elseif (in_array('Rusak', $kondisiList)) {
                            $calculatedKondisi = 'Rusak';
                        } elseif (in_array('Kurang Baik', $kondisiList)) {
                            $calculatedKondisi = 'Kurang Baik';
                        }
                        $data['kondisi'] = $calculatedKondisi;

                        $spec = $service->findOrCreate(
                            $periodeId,
                            $data['spec_details'] ?? [],
                            $data['kondisi']
                        );

                        $pc = RekapInventarisPc::create([
                            'rekap_inventaris_periode_id' => $periodeId,
                            'rekap_inventaris_spec_id' => $spec->id,
                            'no_pc' => $this->generateNextNoPc(),
                            'lokasi' => $data['lokasi'],
                            'kondisi' => $data['kondisi'],
                        ]);

                        $service->syncPeriodSpecOrder($periodeId);

                        return $pc->fresh();
                    }),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('No')
                    ->rowIndex(),

                TextColumn::make('no_pc')
                    ->label('NoPC')
                    ->searchable(),

                TextColumn::make('spec.kode_spek')
                    ->label('Spek')
                    ->color('primary')
                    ->action(
                        Tables\Actions\Action::make('lihatSpek')
                            ->modalHeading(fn (RekapInventarisPc $record) => 'Detail Spesifikasi - ' . ($record->spec?->kode_spek ?? '-'))
                            ->modalWidth('5xl')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->infolist(fn (RekapInventarisPc $record) => [
                                \Filament\Infolists\Components\Section::make($record->spec?->kode_spek ?? 'Detail Spesifikasi')
                                    ->schema(
                                        collect($record->spec?->details ?? [])->map(function ($detail) {
                                            return \Filament\Infolists\Components\Grid::make(4)->schema([
                                                \Filament\Infolists\Components\TextEntry::make('komponen_' . $detail->id)
                                                    ->label('')
                                                    ->state($detail->komponen),

                                                \Filament\Infolists\Components\TextEntry::make('detail_' . $detail->id)
                                                    ->label('Detail')
                                                    ->state($detail->detail ?: '-'),

                                                \Filament\Infolists\Components\TextEntry::make('kondisi_' . $detail->id)
                                                    ->label('Kondisi')
                                                    ->badge()
                                                    ->state($detail->kondisi ?: '-'),

                                                \Filament\Infolists\Components\TextEntry::make('catatan_' . $detail->id)
                                                    ->label('Keterangan')
                                                    ->state($detail->catatan_kondisi ?: '-'),
                                            ]);
                                        })->toArray()
                                    ),
                            ])
                    ),

                TextColumn::make('lokasi')
                    ->label('Lokasi')
                    ->badge(),

                TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Baik' => 'success',
                        'Rusak', 'Rusak Berat' => 'danger',
                        'Kurang Baik', 'Rusak Ringan' => 'warning',
                        'Dalam Perbaikan' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => $state !== 'Baik' ? 'heroicon-m-exclamation-circle' : null)
                    ->action(
                        Tables\Actions\Action::make('lihatDetailKerusakan')
                            ->modalHeading(fn (RekapInventarisPc $record) => 'Detail Masalah - ' . $record->no_pc)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false) // Hide submit button, it's just for viewing
                            ->modalCancelActionLabel('Tutup')
                            ->infolist(fn (RekapInventarisPc $record) => [
                                \Filament\Infolists\Components\Section::make('Daftar Kerusakan Komponen')
                                    ->description('Hanya menampilkan komponen yang bermasalah (Rusak / Kurang Baik).')
                                    ->schema(function() use ($record) {
                                        $issues = collect($record->spec?->details ?? [])
                                            ->filter(fn($detail) => $detail->kondisi !== 'Baik');
                                        
                                        if ($issues->isEmpty()) {
                                            return [\Filament\Infolists\Components\TextEntry::make('no_issues')
                                                ->label('')
                                                ->state('Semua komponen dalam kondisi baik.')];
                                        }

                                        return $issues->map(function ($detail) {
                                            return \Filament\Infolists\Components\Grid::make(3)->schema([
                                                \Filament\Infolists\Components\TextEntry::make('comp_' . $detail->id)
                                                    ->label('Komponen')
                                                    ->state($detail->komponen)
                                                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                                                
                                                \Filament\Infolists\Components\TextEntry::make('cond_' . $detail->id)
                                                    ->label('Kondisi')
                                                    ->state($detail->kondisi)
                                                    ->badge()
                                                    ->color(fn($state) => match($state) {
                                                        'Rusak' => 'danger',
                                                        'Kurang Baik' => 'warning',
                                                        default => 'gray'
                                                    }),
                                                
                                                \Filament\Infolists\Components\TextEntry::make('note_' . $detail->id)
                                                    ->label('Keterangan')
                                                    ->state($detail->catatan_kondisi ?: '-'),
                                            ]);
                                        })->toArray();
                                    }),
                            ])
                    ),

                TextColumn::make('spec_details_issues')
                    ->label('Masalah Komponen')
                    ->state(function (RekapInventarisPc $record) {
                        return collect($record->spec?->details ?? [])
                            ->filter(fn($detail) => !in_array($detail->kondisi, ['Baik', null, '']))
                            ->map(fn($detail) => "{$detail->komponen}: {$detail->kondisi}");
                    })
                    ->listWithLineBreaks()
                    ->bulleted()
                    ->placeholder('Tidak ada (Semua Baik)')
                    ->color('danger')
                    ->extraAttributes(['class' => 'text-xs']),
            ])
            ->actions([

                Tables\Actions\Action::make('copyPrev')
                    ->hiddenLabel()
                    ->icon('img-copy-atas')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large)
                    ->visible(false)
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Copy ke Baris Sebelumnya?')
                    ->modalDescription('Data akan dicopy ke nomor PC sebelumnya jika slot tersebut belum ada.')
                    ->modalSubmitActionLabel('Ya, Copy')
                    ->action(function (RekapInventarisPc $record): void {
                        $currentNumber = $this->extractNoPcNumber($record->no_pc);
                        $prevNumber = $currentNumber - 1;

                        if ($prevNumber <= 0) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Tidak bisa copy ke bawah ' . $this->formatNoPc(1) . '.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $prevNoPc = $this->formatNoPc($prevNumber);

                        $exists = RekapInventarisPc::query()
                            ->where('rekap_inventaris_periode_id', $this->periodeId)
                            ->where('no_pc', $prevNoPc)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Gagal')
                                ->body("{$prevNoPc} sudah tersedia dan tidak bisa dicopy ke baris sebelumnya.")
                                ->danger()
                                ->send();
                            return;
                        }

                        RekapInventarisPc::create([
                            'rekap_inventaris_periode_id' => $this->periodeId,
                            'rekap_inventaris_spec_id' => $record->rekap_inventaris_spec_id,
                            'no_pc' => $prevNoPc,
                            'lokasi' => $record->lokasi,
                            'kondisi' => $record->kondisi,
                        ]);

                        resolve(\App\Services\RekapInventarisSpecService::class)
                            ->syncPeriodSpecOrder($this->periodeId);

                        Notification::make()
                            ->title('Berhasil')
                            ->body("Data berhasil dicopy ke {$prevNoPc}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('copyNext')
                    ->hiddenLabel()
                    ->icon('img-copy-bawah')
                    ->iconSize(\Filament\Support\Enums\IconSize::Large)
                    ->visible(false)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Copy ke Baris Berikutnya?')
                    ->modalDescription('Data akan dicopy ke nomor PC berikutnya jika slot tersebut belum ada.')
                    ->modalSubmitActionLabel('Ya, Copy')
                    ->action(function (RekapInventarisPc $record): void {
                        $currentNumber = $this->extractNoPcNumber($record->no_pc);
                        $nextNumber = $currentNumber + 1;
                        $nextNoPc = $this->formatNoPc($nextNumber);

                        $exists = RekapInventarisPc::query()
                            ->where('rekap_inventaris_periode_id', $this->periodeId)
                            ->where('no_pc', $nextNoPc)
                            ->exists();

                        if ($exists) {
                            Notification::make()
                                ->title('Gagal')
                                ->body("{$nextNoPc} sudah tersedia dan tidak bisa dicopy ke baris berikutnya.")
                                ->danger()
                                ->send();
                            return;
                        }

                        RekapInventarisPc::create([
                            'rekap_inventaris_periode_id' => $this->periodeId,
                            'rekap_inventaris_spec_id' => $record->rekap_inventaris_spec_id,
                            'no_pc' => $nextNoPc,
                            'lokasi' => $record->lokasi,
                            'kondisi' => $record->kondisi,
                        ]);

                        resolve(\App\Services\RekapInventarisSpecService::class)
                            ->syncPeriodSpecOrder($this->periodeId);

                        Notification::make()
                            ->title('Berhasil')
                            ->body("Data berhasil dicopy ke {$nextNoPc}.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->modalWidth('7xl')
                    ->visible(!auth()->user()->hasRole('super_admin'))
                    ->fillForm(function (RekapInventarisPc $record): array {
                        $details = [];

                        foreach ($record->spec?->details ?? [] as $detail) {
                            $map = [
                                'Motherboard' => 1,
                                'Processor' => 2,
                                'Hardisk' => 3,
                                'VGA' => 4,
                                'RAM' => 5,
                                'DVD' => 6,
                                'Keyboard' => 7,
                                'Mouse' => 8,
                                'Monitor' => 9,
                            ];

                            $index = $map[$detail->komponen] ?? null;

                            if ($index) {
                                $details[$index] = [
                                    'detail' => $detail->detail,
                                    'kondisi' => $detail->kondisi,
                                    'catatan_kondisi' => $detail->catatan_kondisi,
                                ];
                            }
                        }

                        return [
                            'no_pc_preview' => $record->no_pc,
                            'lokasi' => $record->lokasi,
                            'kondisi' => $record->kondisi,
                            'spec_details' => $details,
                        ];
                    })
                    ->form($this->getPcFormSchema(isEdit: true))
                    ->using(function (RekapInventarisPc $record, array $data) {
                        $periodeId = $this->periodeId;
                        $service = resolve(\App\Services\RekapInventarisSpecService::class);

                        $details = $data['spec_details'] ?? [];
                        $kondisiList = collect($details)->pluck('kondisi')->filter()->toArray();
                        $calculatedKondisi = 'Baik';
                        if (in_array('Tidak Ada', $kondisiList)) {
                            $calculatedKondisi = 'Tidak Ada';
                        } elseif (in_array('Rusak', $kondisiList)) {
                            $calculatedKondisi = 'Rusak';
                        } elseif (in_array('Kurang Baik', $kondisiList)) {
                            $calculatedKondisi = 'Kurang Baik';
                        }
                        $data['kondisi'] = $calculatedKondisi;

                        $incomingFingerprint = $service->fingerprintFromDetails(
                            $data['spec_details'] ?? [],
                            $data['kondisi']
                        );

                        if (
                            $record->spec &&
                            $record->spec->fingerprint === $incomingFingerprint
                        ) {
                            $record->update([
                                'lokasi' => $data['lokasi'],
                                'kondisi' => $data['kondisi'],
                            ]);

                            $this->updateSpecDetails($record, $data['spec_details'] ?? []);
                        } else {
                            $spec = $service->findOrCreate(
                                $periodeId,
                                $data['spec_details'] ?? [],
                                $data['kondisi']
                            );

                            $record->update([
                                'rekap_inventaris_spec_id' => $spec->id,
                                'lokasi' => $data['lokasi'],
                                'kondisi' => $data['kondisi'],
                            ]);
                        }

                        $service->syncPeriodSpecOrder($periodeId);

                        return $record->fresh();
                    }),

                Tables\Actions\Action::make('ajukanLaporan')
                    ->label('Ajukan Laporan')
                    ->icon('heroicon-o-megaphone')
                    ->color('danger')
                    ->visible(fn() => auth()->user() && !auth()->user()->hasRole('super_admin'))
                    ->disabled(fn(RekapInventarisPc $record) => collect($record->spec?->details ?? [])->filter(fn($detail) => in_array($detail->kondisi, ['Kurang Baik', 'Rusak', 'Tidak Ada']))->isEmpty())
                    ->form(function (RekapInventarisPc $record) {
                        $problematic = collect($record->spec?->details ?? [])
                            ->filter(fn($detail) => in_array($detail->kondisi, ['Kurang Baik', 'Rusak', 'Tidak Ada']))
                            ->mapWithKeys(fn($detail) => [$detail->komponen => "{$detail->komponen} ({$detail->kondisi})"])
                            ->toArray();

                        return [
                            Select::make('komponen')
                                ->label('Pilih Komponen Bermasalah')
                                ->options($problematic)
                                ->placeholder(empty($problematic) ? 'Tidak ada komponen bermasalah' : 'Pilih komponen...')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set, RekapInventarisPc $record) {
                                    if ($state) {
                                        $detail = collect($record->spec?->details ?? [])->firstWhere('komponen', $state);
                                        if ($detail) {
                                            $set('kondisi', $detail->kondisi);
                                            $set('keterangan', $detail->catatan_kondisi);
                                        }
                                    }
                                }),
                            
                            TextInput::make('kondisi')
                                ->label('Kondisi Komponen')
                                ->readOnly()
                                ->required(),

                            Select::make('prioritas')
                                ->label('Prioritas')
                                ->options([
                                    'Rendah' => 'Rendah',
                                    'Sedang' => 'Sedang',
                                    'Tinggi' => 'Tinggi',
                                ])
                                ->default('Sedang')
                                ->required(),

                            Textarea::make('keterangan')
                                ->label('Keterangan Kerusakan')
                                ->required(),
                        ];
                    })
                    ->action(function (RekapInventarisPc $record, array $data) {
                        $labId = $this->laboratoriumId ?? $record->periode->laboratorium_id;
                        $labName = \App\Models\Laboratorium::find($labId)?->ruang ?? 'Unknown';

                        \App\Models\LaporanPerbaikan::create([
                            'rekap_inventaris_pc_id' => $record->id,
                            'inventory_id' => $record->inventory_id,
                            'periode_id' => $record->rekap_inventaris_periode_id,
                            'komponen' => $data['komponen'],
                            'kondisi' => $data['kondisi'],
                            'laboratorium_id' => $labId,
                            'no_pc' => $record->no_pc,
                            'kode_pc' => $record->inventory?->kode_pc,
                            'ruang_lab' => $labName,
                            'prioritas' => $data['prioritas'],
                            'keterangan' => $data['keterangan'],
                            'komponen_rusak' => [
                                [
                                    'komponen' => $data['komponen'],
                                    'kondisi' => $data['kondisi'],
                                    'keterangan' => $data['keterangan'],
                                ]
                            ],
                            'status' => 'Menunggu',
                            'tanggal_pengajuan' => now(),
                            'user_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Laporan Berhasil Diajukan')
                            ->body("Kerusakan komponen {$data['komponen']} pada PC {$record->no_pc} telah dilaporkan.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->visible(false)
                    ->after(function () {
                        $periodeId = $this->periodeId;
                        $service = resolve(\App\Services\RekapInventarisSpecService::class);
                        $service->syncPeriodSpecOrder($periodeId);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('laporkanPerbaikan')
                        ->label('Laporkan Perbaikan')
                        ->icon('heroicon-o-megaphone')
                        ->color('danger')
                        ->visible(fn() => auth()->user() && !auth()->user()->hasRole('super_admin'))
                        ->modalHeading('Konfirmasi Laporan Perbaikan')
                        ->modalDescription(fn (\Illuminate\Database\Eloquent\Collection $records) => new \Illuminate\Support\HtmlString('Anda akan melaporkan PC berikut: <strong>' . $records->pluck('no_pc')->join(', ') . '</strong>. Apakah Anda yakin ingin melanjutkan?\\nLaporan PDF PTPP akan diunduh setelah konfirmasi.'))
                        ->modalSubmitActionLabel('Kirim & Unduh Laporan')
                        ->modalCancelActionLabel('Batal')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('ketidaksesuaian')
                                ->label('Bentuk Ketidaksesuaian')
                                ->default('Kerusakan Hardware/Software Inventaris')
                                ->required(),
                            \Filament\Forms\Components\TextInput::make('tanggal_kejadian')
                                ->label('Tanggal & Waktu Kejadian')
                                ->placeholder('Contoh: 2 April 2024 Jam: 07.00 - 09.00')
                                ->required(),
                            \Filament\Forms\Components\Textarea::make('tindakan_langsung')
                                ->label('Tindakan Langsung')
                                ->default('Melaporkan kerusakan inventaris ke Super Admin.')
                                ->required(),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $count = 0;
                            $problematic_pcs = [];
                            $summary_counts = [];
                            $labName = 'Semua Laboratorium';

                            foreach ($records as $record) {
                                if ($record->kondisi === 'Baik') {
                                    $hasIssue = collect($record->spec?->details ?? [])
                                        ->contains(fn($detail) => !empty($detail->kondisi) && $detail->kondisi !== 'Baik');
                                    if (!$hasIssue) {
                                        continue; 
                                    }
                                }

                                $labId = $this->laboratoriumId ?? $record->periode->laboratorium_id;
                                $labName = \App\Models\Laboratorium::find($labId)?->ruang ?? 'Unknown';

                                $komponenRusak = [];
                                $broken_components_list = [];

                                foreach ($record->spec?->details ?? [] as $detail) {
                                    if (!empty($detail->kondisi) && $detail->kondisi !== 'Baik') {
                                        $komponenRusak[] = [
                                            'komponen' => $detail->komponen,
                                            'kondisi' => $detail->kondisi,
                                            'keterangan' => $detail->catatan_kondisi,
                                        ];
                                        
                                        $broken_components_list[] = $detail->komponen . ' (' . strtolower($detail->kondisi) . ')';
                                        
                                        $komp_name = $detail->komponen;
                                        if (!isset($summary_counts[$komp_name])) {
                                            $summary_counts[$komp_name] = 0;
                                        }
                                        $summary_counts[$komp_name]++;
                                    }
                                }

                                \App\Models\LaporanPerbaikan::create([
                                    'rekap_inventaris_pc_id' => $record->id,
                                    'inventory_id' => $record->inventory_id,
                                    'periode_id' => $record->rekap_inventaris_periode_id,
                                    'laboratorium_id' => $labId,
                                    'no_pc' => $record->no_pc,
                                    'kode_pc' => $record->inventory?->kode_pc,
                                    'ruang_lab' => $labName,
                                    'prioritas' => 'Sedang',
                                    'komponen_rusak' => $komponenRusak,
                                    'keterangan' => $data['tindakan_langsung'],
                                    'status' => 'Menunggu',
                                    'tanggal_pengajuan' => now()->toDateString(),
                                    'user_id' => auth()->id(),
                                ]);
                                
                                if (count($broken_components_list) > 0) {
                                    $kodeStr = $record->inventory?->kode_pc ? " (Kode PC: " . $record->inventory->kode_pc . ")" : "";
                                    $problematic_pcs[] = "- PC " . $record->no_pc . $kodeStr . ": " . implode(', ', $broken_components_list);
                                }
                                $count++;
                            }

                            if ($count === 0) {
                                \Filament\Notifications\Notification::make()
                                    ->title("Tidak ada PC bermasalah yang dapat dilaporkan")
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $uraian = implode("\\n", $problematic_pcs);
                            $perbaikan_list = [];
                            foreach ($summary_counts as $komp => $qty) {
                                $perbaikan_list[] = "- Penggantian $komp ($qty unit)";
                            }
                            $tindakan_perbaikan = implode("\\n", $perbaikan_list);

                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.laporan-pengajuan', [
                                'nomor' => 'F.LAB.KOM-UDINUS-SH-03-02',
                                'revisi' => '0',
                                'tanggal_berlaku' => '19 September 2022',
                                'ketidaksesuaian' => $data['ketidaksesuaian'],
                                'lab' => $labName,
                                'tanggal' => $data['tanggal_kejadian'],
                                'uraian' => $uraian,
                                'tindakan_langsung' => $data['tindakan_langsung'],
                                'tindakan_perbaikan' => $tindakan_perbaikan,
                                'pelapor' => auth()->user()->name,
                                'jabatan_pelapor' => 'Laboran',
                                'admin' => '............................',
                                'jabatan_admin' => 'Super Admin',
                            ])->setPaper('a4', 'portrait');

                            $filename = "PTPP_" . str_replace(' ', '_', $labName) . "_" . date('Ymd_Hi') . ".pdf";

                            return response()->streamDownload(fn () => print($pdf->output()), $filename);
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50]);
    }

    protected function getPcFormSchema(bool $isEdit = false): array
    {
        return [
            Grid::make(3)->schema([
                $isEdit
                    ? Placeholder::make('no_pc_preview')
                        ->label('NoPC')
                        ->content(fn ($state) => $state ?: '-')
                    : Placeholder::make('no_pc_info')
                        ->label('NoPC')
                        ->content(fn () => 'Otomatis dibuat saat data disimpan'),

                Select::make('lokasi')
                    ->label('Lokasi')
                    ->options([
                        'Client' => 'Client',
                        'Laboran' => 'Laboran',
                        'Dosen' => 'Dosen',
                    ])
                    ->required(),

                Placeholder::make('kondisi_placeholder')
                    ->label('Kondisi PC (Otomatis)')
                    ->content(function (Get $get) {
                        $details = $get('spec_details') ?? [];
                        $kondisiList = collect($details)->pluck('kondisi')->filter()->toArray();
                        if (in_array('Tidak Ada', $kondisiList)) {
                            return 'Tidak Ada';
                        }
                        if (in_array('Rusak', $kondisiList)) {
                            return 'Rusak';
                        }
                        if (in_array('Kurang Baik', $kondisiList)) {
                            return 'Kurang Baik';
                        }
                        return 'Baik';
                    }),
            ]),

            Section::make('Detail Spesifikasi')
                ->schema($this->getSpecDetailFormSchema())
                ->columns(1),
        ];
    }

    protected function getSpecDetailFormSchema(): array
    {
        // Map komponen to: [index, model class, label for field]
        $componentConfig = [
            1 => ['label' => 'Motherboard', 'model' => \App\Models\Motherboard::class],
            2 => ['label' => 'Processor',   'model' => \App\Models\Processor::class],
            3 => ['label' => 'Hardisk',     'model' => \App\Models\Penyimpanan::class],
            4 => ['label' => 'VGA',         'model' => \App\Models\VGA::class],
            5 => ['label' => 'RAM',         'model' => \App\Models\RAM::class],
            6 => ['label' => 'DVD',         'model' => \App\Models\DVD::class],
            7 => ['label' => 'Keyboard',    'model' => \App\Models\Keyboard::class],
            8 => ['label' => 'Mouse',       'model' => \App\Models\Mouse::class],
            9 => ['label' => 'Monitor',     'model' => \App\Models\Monitor::class],
        ];

        $schema = [];

        foreach ($componentConfig as $index => $config) {
            $label = $config['label'];
            $modelClass = $config['model'];

            // Build dropdown options: key = full_name string, value = full_name string
            // This keeps the 'detail' column as a string in the database (no extra FK needed)
            $options = $modelClass::all()->mapWithKeys(function ($item) {
                return [$item->full_name => $item->full_name];
            })->toArray();

            $schema[] = Section::make($label)
                ->schema([
                    Grid::make(3)->schema([
                        Select::make("spec_details.$index.detail")
                            ->label('Tipe')
                            ->options($options)
                            ->searchable()
                            ->nullable()
                            ->placeholder('Pilih tipe ' . $label),

                        Select::make("spec_details.$index.kondisi")
                            ->label('Kondisi')
                            ->options([
                                'Baik'       => 'Baik',
                                'Kurang Baik' => 'Kurang Baik',
                                'Rusak'      => 'Rusak',
                                'Tidak Ada'  => 'Tidak Ada',
                            ])
                            ->placeholder('-')
                            ->nullable()
                            ->live(),

                        TextInput::make("spec_details.$index.catatan_kondisi")
                            ->label('Keterangan'),
                    ]),
                ]);
        }

        return $schema;
    }


    protected function generateNextNoPc(): string
    {
        $existing = RekapInventarisPc::query()
            ->where('rekap_inventaris_periode_id', $this->periodeId)
            ->pluck('no_pc')
            ->toArray();

        $numbers = array_map(function ($no) {
            return $this->extractNoPcNumber($no);
        }, $existing);

        sort($numbers);

        $expected = 1;

        foreach ($numbers as $num) {
            if ($num !== $expected) {
                break;
            }
            $expected++;
        }

        return $this->formatNoPc($expected);
    }

    protected function extractNoPcNumber(string $noPc): int
    {
        return (int) preg_replace('/[^0-9]/', '', $noPc);
    }

    protected function formatNoPc(int $number): string
    {
        $prefix = 'B';
        
        if ($this->laboratoriumId) {
            $lab = \App\Models\Laboratorium::find($this->laboratoriumId);
            if ($lab) {
                $prefix = substr($lab->ruang, -1);
            }
        } else {
            // Fallback: try to get from period
            $periode = \App\Models\RekapInventarisPeriode::with('laboratorium')->find($this->periodeId);
            if ($periode && $periode->laboratorium) {
                $prefix = substr($periode->laboratorium->ruang, -1);
            }
        }

        return $prefix . str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    protected function updateSpecDetails(RekapInventarisPc $record, array $details): void
    {
        if (! $record->spec) {
            return;
        }

        $map = [
            1 => 'Motherboard',
            2 => 'Processor',
            3 => 'Hardisk',
            4 => 'VGA',
            5 => 'RAM',
            6 => 'DVD',
            7 => 'Keyboard',
            8 => 'Mouse',
            9 => 'Monitor',
        ];

        foreach ($map as $index => $komponen) {
            $detailRecord = $record->spec->details
                ->firstWhere('komponen', $komponen);

            if (! $detailRecord) {
                continue;
            }

            $detailRecord->update([
                'detail' => trim((string) ($details[$index]['detail'] ?? '')),
                'kondisi' => $details[$index]['kondisi'] ?? null,
                'catatan_kondisi' => trim((string) ($details[$index]['catatan_kondisi'] ?? '')),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.rekap-inventaris.pc-table');
    }
}