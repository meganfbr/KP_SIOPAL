<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LaporanPerbaikanResource\Pages;
use App\Models\LaporanPerbaikan;
use App\Models\Laboratorium;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LaporanPerbaikanResource extends Resource
{
    protected static ?string $model = LaporanPerbaikan::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Inventaris';
    protected static ?string $navigationLabel = 'Laporan Pengajuan';
    protected static ?string $modelLabel = 'Laporan Perbaikan';
    protected static ?string $pluralModelLabel = 'Laporan Perbaikan';
    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        return self::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        if ($user->hasRole('super_admin')) return true;
        return $user->roles->pluck('name')->contains(fn ($n) => str_starts_with($n, 'Laboran_'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && !$user->hasRole('super_admin')) {
            $role = $user->roles->firstWhere(fn($r) => str_starts_with($r->name, 'Laboran_'));
            if ($role) {
                $labSlug = str_replace('Laboran_', '', $role->name);
                $labInfo = Laboratorium::where('ruang', 'LAB ' . strtoupper($labSlug))->first();
                if ($labInfo) {
                    $query->where('laboratorium_id', $labInfo->id);
                } else {
                    $query->where('id', 0);
                }
            }
        }
        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Laporan')
                    ->schema([
                        Forms\Components\TextInput::make('no_pc')
                            ->label('Nomor PC')
                            ->disabled(),
                        Forms\Components\TextInput::make('ruang_lab')
                            ->label('Laboratorium')
                            ->disabled(),
                        Forms\Components\TextInput::make('komponen')
                            ->label('Komponen')
                            ->disabled(),
                        Forms\Components\TextInput::make('kondisi')
                            ->label('Kondisi')
                            ->disabled(),
                        Forms\Components\Select::make('prioritas')
                            ->options([
                                'Rendah' => 'Rendah',
                                'Sedang' => 'Sedang',
                                'Tinggi' => 'Tinggi',
                            ])
                            ->disabled(fn() => !auth()->user()->hasRole('super_admin'))
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'Menunggu' => 'Menunggu',
                                'Diproses' => 'Diproses',
                                'Selesai' => 'Selesai',
                                'Ditolak' => 'Ditolak',
                            ])
                            ->disabled(fn() => !auth()->user()->hasRole('super_admin'))
                            ->required(),
                        Forms\Components\DatePicker::make('tanggal_pengajuan')
                            ->label('Tanggal Pengajuan')
                            ->disabled(),
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->disabled(fn() => !auth()->user()->hasRole('super_admin')),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tanggal_pengajuan')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_pc')
                    ->label('No. PC')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ruang_lab')
                    ->label('Laboratorium')
                    ->searchable(),
                Tables\Columns\TextColumn::make('komponen')
                    ->label('Komponen')
                    ->state(fn ($record) => $record->komponen ?: (collect($record->komponen_rusak)->map(function($item) {
                        return is_array($item) ? $item['komponen'] : $item;
                    })->join(', ')))
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->state(fn ($record) => $record->kondisi ?: (collect($record->komponen_rusak)->map(function($item) {
                        return is_array($item) ? $item['kondisi'] : 'Rusak';
                    })->join(', ')))
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Kurang Baik' => 'warning',
                        'Rusak' => 'danger',
                        'Tidak Ada' => 'gray',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap(),
                Tables\Columns\TextColumn::make('prioritas')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Rendah' => 'success',
                        'Sedang' => 'warning',
                        'Tinggi' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Menunggu' => 'gray',
                        'Diproses' => 'warning',
                        'Selesai' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('tanggal_pengajuan', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('laboratorium_id')
                    ->label('Laboratorium')
                    ->options(Laboratorium::pluck('ruang', 'id'))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Menunggu' => 'Menunggu',
                        'Diproses' => 'Diproses',
                        'Selesai' => 'Selesai',
                        'Ditolak' => 'Ditolak',
                    ]),
            ], layout: Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->headerActions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('export_pdf')
                        ->label('Cetak Pengajuan (PDF)')
                        ->icon('heroicon-o-document-text')
                        ->action(function (Table $table) {
                            $records = $table->getLivewire()->getFilteredTableQuery()->with('user')->get();
                            
                            if ($records->isEmpty()) {
                                \Filament\Notifications\Notification::make()->title('Data kosong')->warning()->send();
                                return;
                            }

                            $problematic_pcs = [];
                            $summary_counts = [];
                            
                            $labNames = $records->pluck('ruang_lab')->filter()->unique();
                            $labName = $labNames->count() === 1 ? $labNames->first() : 'Semua Laboratorium';
                            
                            $pelaporNames = $records->pluck('user.name')->filter()->unique();
                            $pelaporName = $pelaporNames->count() === 1 ? $pelaporNames->first() : 'Laboran';
                            
                            $catatanList = $records->pluck('keterangan')->filter()->unique()->toArray();
                            $catatan = empty($catatanList) ? 'Melaporkan kerusakan inventaris ke Super Admin.' : implode(', ', $catatanList);

                            foreach ($records as $record) {
                                $komponenRusakStr = [];
                                foreach (collect($record->komponen_rusak) as $k) {
                                    $kompName = is_array($k) ? $k['komponen'] : $k;
                                    $kondisi = is_array($k) && isset($k['kondisi']) ? $k['kondisi'] : 'Rusak';
                                    $komponenRusakStr[] = $kompName . ' (' . strtolower($kondisi) . ')';
                                    
                                    if (!isset($summary_counts[$kompName])) {
                                        $summary_counts[$kompName] = 0;
                                    }
                                    $summary_counts[$kompName]++;
                                }
                                
                                if (count($komponenRusakStr) > 0) {
                                    $problematic_pcs[] = "- PC " . $record->no_pc . ": " . implode(', ', $komponenRusakStr);
                                }
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
                                'ketidaksesuaian' => 'Kerusakan Hardware/Software Inventaris',
                                'lab' => $labName,
                                'tanggal' => date('d F Y'),
                                'uraian' => $uraian,
                                'tindakan_langsung' => $catatan,
                                'tindakan_perbaikan' => $tindakan_perbaikan,
                                'pelapor' => $pelaporName,
                                'jabatan_pelapor' => 'Laboran',
                                'admin' => auth()->user()->name,
                                'jabatan_admin' => 'Super Admin',
                            ])->setPaper('a4', 'portrait');
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()),
                                "Pengajuan_Perbaikan_" . now()->format('YmdHis') . ".pdf"
                            );
                        }),
                    Tables\Actions\Action::make('export_excel')
                        ->label('Export Excel')
                        ->icon('heroicon-o-table-cells')
                        ->action(function (Table $table) {
                            $query = $table->getLivewire()->getFilteredTableQuery();
                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\LaporanPerbaikanExport($query),
                                "Laporan_Perbaikan_" . now()->format('YmdHis') . ".xlsx"
                            );
                        }),
                ])
                ->label('Download Laporan')
                ->icon('heroicon-o-arrow-down-tray')
                ->button()
                ->color('success'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('export_single_pdf')
                    ->label('Cetak PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('info')
                    ->action(function (LaporanPerbaikan $record) {
                        $periodeText = $record->periode 
                            ? \Illuminate\Support\Carbon::create(null, $record->periode->bulan)->translatedFormat('F') . ' ' . $record->periode->tahun
                            : '-';

                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.laporan-pengajuan-tunggal', [
                            'lab' => $record->ruang_lab,
                            'no_pc' => $record->no_pc,
                            'komponen' => $record->komponen ?: (collect($record->komponen_rusak)->pluck('komponen')->join(', ')),
                            'kondisi' => $record->kondisi ?: (collect($record->komponen_rusak)->pluck('kondisi')->join(', ')),
                            'keterangan' => $record->keterangan,
                            'periode' => $periodeText,
                            'pelapor' => $record->user?->name ?: 'Laboran',
                            'status' => $record->status,
                            'tanggal' => $record->tanggal_pengajuan ? $record->tanggal_pengajuan->format('d M Y') : now()->format('d M Y'),
                        ])->setPaper('a4', 'portrait');

                        return response()->streamDownload(
                            fn () => print($pdf->output()),
                            "Laporan_Kerusakan_{$record->no_pc}_" . now()->format('YmdHis') . ".pdf"
                        );
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()->hasRole('super_admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->hasRole('super_admin')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanPerbaikans::route('/'),
        ];
    }
}
