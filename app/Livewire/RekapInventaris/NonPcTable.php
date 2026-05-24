<?php

namespace App\Livewire\RekapInventaris;

use App\Models\NonPCDetail;
use App\Models\RekapInventarisNonPc;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class NonPcTable extends Component implements HasForms, HasTable
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

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RekapInventarisNonPc::query()
                    ->where('rekap_inventaris_periode_id', $this->periodeId)
            )
            ->heading('Rekap Inventaris Non-PC')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Inventaris Non-PC')
                    ->visible(fn() => auth()->user()->hasRole('super_admin'))
                    ->modalHeading('Tambah Barang Non-PC ke Rekap')
                    ->form([
                        Select::make('non_pc_detail_id')
                            ->label('Pilih Barang dari Master')
                            ->options(NonPCDetail::all()->mapWithKeys(function ($item) {
                                return [$item->id => "{$item->nama} - {$item->merk} ({$item->model})"];
                            }))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $detail = NonPCDetail::find($state);
                                    if ($detail) {
                                        $set('nama_barang', $detail->nama);
                                        $set('merk_model', "{$detail->merk} ({$detail->model})");
                                    }
                                }
                            }),
                        
                        TextInput::make('nama_barang')
                            ->label('Nama Barang')
                            ->required()
                            ->readOnly(),
                        
                        TextInput::make('merk_model')
                            ->label('Merk/Model')
                            ->required()
                            ->readOnly(),
                        
                        TextInput::make('jumlah')
                            ->label('Jumlah')
                            ->numeric()
                            ->default(1)
                            ->required(),
                        
                        Select::make('kondisi')
                            ->label('Kondisi')
                            ->options([
                                'Baik' => 'Baik',
                                'Rusak Ringan' => 'Rusak Ringan',
                                'Rusak Berat' => 'Rusak Berat',
                                'Dalam Perbaikan' => 'Dalam Perbaikan',
                            ])
                            ->required()
                            ->default('Baik'),
                        
                        Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Opsional'),
                    ])
                    ->action(function (array $data) {
                        RekapInventarisNonPc::create([
                            'rekap_inventaris_periode_id' => $this->periodeId,
                            'nama_barang' => $data['nama_barang'],
                            'merk_model' => $data['merk_model'],
                            'jumlah' => $data['jumlah'],
                            'kondisi' => $data['kondisi'],
                            'keterangan' => $data['keterangan'] ?? null,
                        ]);
                    }),
            ])
            ->columns([
                TextColumn::make('nama_barang')
                    ->label('Nama Barang')
                    ->searchable(),
                
                TextColumn::make('merk_model')
                    ->label('Merk/Model'),
                
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->sortable(),
                
                TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Baik' => 'success',
                        'Rusak Ringan', 'Rusak Berat' => 'danger', // Red for both as requested
                        'Dalam Perbaikan' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): ?string => $state !== 'Baik' ? 'heroicon-m-exclamation-circle' : null)
                    ->action(
                        Tables\Actions\Action::make('lihatDetailMasalah')
                            ->modalHeading(fn (RekapInventarisNonPc $record) => 'Detail Kondisi - ' . $record->nama_barang)
                            ->modalWidth('2xl')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                            ->infolist(fn (RekapInventarisNonPc $record) => [
                                \Filament\Infolists\Components\Section::make('Keterangan Kondisi')
                                    ->schema([
                                        \Filament\Infolists\Components\Grid::make(2)->schema([
                                            \Filament\Infolists\Components\TextEntry::make('kondisi')
                                                ->badge()
                                                ->color(fn ($state) => match ($state) {
                                                    'Baik' => 'success',
                                                    'Rusak Ringan', 'Rusak Berat' => 'danger',
                                                    'Dalam Perbaikan' => 'info',
                                                    default => 'gray',
                                                }),
                                            \Filament\Infolists\Components\TextEntry::make('jumlah')
                                                ->state($record->jumlah . ' Unit'),
                                        ]),
                                        \Filament\Infolists\Components\TextEntry::make('keterangan')
                                            ->label('Catatan Keterangan')
                                            ->state($record->keterangan ?: 'Tidak ada keterangan tambahan.'),
                                    ])
                            ])
                    ),
                
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(50),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn() => auth()->user()->hasRole('super_admin'))
                    ->form([
                        TextInput::make('nama_barang')->readOnly(),
                        TextInput::make('merk_model')->readOnly(),
                        TextInput::make('jumlah')->numeric()->required(),
                        Select::make('kondisi')
                            ->options([
                                'Baik' => 'Baik',
                                'Rusak Ringan' => 'Rusak Ringan',
                                'Rusak Berat' => 'Rusak Berat',
                                'Dalam Perbaikan' => 'Dalam Perbaikan',
                            ])
                            ->required(),
                        Textarea::make('keterangan'),
                    ]),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn() => auth()->user()->hasRole('super_admin')),
            ]);
    }

    public function render()
    {
        return view('livewire.rekap-inventaris.non-pc-table');
    }
}
