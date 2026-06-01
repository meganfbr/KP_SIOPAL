<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PCInventoryResource\Pages;
use App\Models\Inventory;
use App\Models\PCDetail;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\Action;
use App\Models\Laboratorium;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Infolist;

class PCInventoryResource extends Resource
{
    protected static ?string $model = Inventory::class;

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->hasRole('super_admin') || $user->roles->pluck('name')->contains(fn ($name) => str_starts_with($name, 'Laboran_'));
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        $isLaboran = $user->roles->pluck('name')->contains(fn ($name) => str_starts_with($name, 'Laboran_'));
        if ($isLaboran) {
            // Laboran hanya boleh edit PC yang ada di wilayah/laboratorium mereka
            return in_array($record->laboratorium_id, $user->getAuthorizedLabIds('view'));
        }
        
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    protected static ?string $modelLabel = 'Inventaris PC';
    protected static ?string $pluralModelLabel = 'Inventaris PC';
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    // Sembunyikan dari navigasi utama karena sudah dibuat dinamis
    protected static bool $shouldRegisterNavigation = false;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->where('inventoriable_type', PCDetail::class)->with('pcComponents');

        // Filter by user's authorized labs
        $user = auth()->user();
        if ($user && !$user->hasRole('super_admin')) {
            $authorizedLabIds = $user->getAuthorizedLabIds('view');
            $query->whereIn('laboratorium_id', $authorizedLabIds);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Umum PC')
                    ->schema([
                        Select::make('laboratorium_id')
                            ->label('Laboratorium')
                            ->relationship('laboratorium', 'ruang')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->live()
                            ->default(function () {
                                // Auto-fill berdasarkan URL parameter jika ada
                                $labId = request()->input('tableFilters.laboratorium.value')
                                    ?? request()->input('tableFilters')['laboratorium']['value'] ?? null;

                                if ($labId) {
                                    return (int) $labId;
                                }
                                return null;
                            })
                            ->afterStateHydrated(function ($component, $state) {
                                // Hook ini dipanggil setelah form dimuat
                                if (!$state) {
                                    $labId = request()->input('tableFilters.laboratorium.value')
                                        ?? request()->input('tableFilters')['laboratorium']['value'] ?? null;

                                    if ($labId) {
                                        $component->state((int) $labId);
                                    }
                                }
                            })
                            ->hidden(function () {
                                // Sembunyikan field jika ada parameter lab di URL
                                $labId = request()->input('tableFilters.laboratorium.value')
                                    ?? request()->input('tableFilters')['laboratorium']['value'] ?? null;
                                return (bool) $labId;
                            })
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    // Ambil nama laboratorium
                                    $laboratorium = \App\Models\Laboratorium::find($state);
                                    $namaLab = $laboratorium ? strtoupper($laboratorium->ruang) : 'LAB';

                                    // Cari nomor urut tertinggi yang pernah digunakan untuk lab ini
                                    $lastInventory = \App\Models\Inventory::where('laboratorium_id', $state)
                                        ->where('inventoriable_type', 'App\Models\PCDetail')
                                        ->whereNotNull('kode_inventaris')
                                        ->orderByRaw("CAST(SUBSTRING_INDEX(kode_inventaris, '/', -1) AS UNSIGNED) DESC")
                                        ->first();

                                    $lastNumber = 0;
                                    if ($lastInventory && $lastInventory->kode_inventaris) {
                                        $parts = explode('/', $lastInventory->kode_inventaris);
                                        $lastNumber = (int) end($parts);
                                    }

                                    $nomorUrut = str_pad($lastNumber + 1, 2, '0', STR_PAD_LEFT);

                                    // Set nomor inventaris yang akan di-generate
                                    $set('preview_kode_inventaris', "UDN/LABKOM/INV/PC/{$namaLab}/{$nomorUrut}");
                                } else {
                                    $set('preview_kode_inventaris', null);
                                }
                            }),
                        TextInput::make('preview_kode_inventaris')
                            ->label('No Inventaris (Preview)')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Pilih laboratorium terlebih dahulu')
                            ->helperText('Nomor inventaris yang akan di-generate otomatis')
                            ->extraAttributes(['style' => 'background-color: #f3f4f6; font-weight: 500;']),
                        TextInput::make('kode_pc')
                            ->label('Kode PC')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                        TextInput::make('no_pc')
                            ->label('No PC')
                            ->disabled()
                            ->dehydrated(false)
                            ->hiddenOn('create'),
                        Select::make('asal_id')
                            ->label('Asal PC')
                            ->relationship('asal', 'ruang')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Pengadaan Baru')
                            ->hiddenOn('create'),
                        DatePicker::make('tanggal_pengadaan')
                            ->disabled(fn () => !auth()->user()->hasRole('super_admin')),
                        Select::make('kondisi')
                            ->options(['Baik' => 'Baik', 'Rusak Ringan' => 'Rusak Ringan', 'Rusak Berat' => 'Rusak Berat', 'Dalam Perbaikan' => 'Dalam Perbaikan'])
                            ->required()
                            ->default('Baik'),
                    ])->columns(2)
                    ->extraAttributes(function () {
                        // Auto-trigger afterStateUpdated untuk preview kode inventaris
                        $labId = request()->input('tableFilters.laboratorium.value')
                            ?? request()->input('tableFilters')['laboratorium']['value'] ?? null;

                        if ($labId) {
                            return [
                                'x-data' => '{
                                    init() {
                                        this.$nextTick(() => {
                                            const labSelect = this.$el.querySelector(\'[wire\\:model*="laboratorium_id"]\');
                                            if (labSelect && labSelect.value) {
                                                labSelect.dispatchEvent(new Event(\'change\', { bubbles: true }));
                                            }
                                        });
                                    }
                                }'
                            ];
                        }
                        return [];
                    }),

                Section::make('Spesifikasi Komponen PC')
                    ->description('Pilih komponen dari master data yang tersedia.')
                    ->schema([
                        // Data di dalam section ini akan disimpan ke tabel pc_details
                        // melalui logika di halaman Create/Edit
                        Grid::make(3)->schema([
                            Select::make('details.processor_id')
                                ->label('Processor')
                                ->options(function () {
                                    return \App\Models\Processor::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Pilih Processor'),
                            Select::make('details.motherboard_id')
                                ->label('Motherboard')
                                ->options(function () {
                                    return \App\Models\Motherboard::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Pilih Motherboard'),
                            Select::make('details.ram_id')
                                ->label('RAM')
                                ->options(function () {
                                    return \App\Models\RAM::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Pilih RAM'),
                            Select::make('details.penyimpanan_id')
                                ->label('Penyimpanan')
                                ->options(function () {
                                    return \App\Models\Penyimpanan::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Pilih Penyimpanan'),
                            Select::make('details.vga_id')
                                ->label('VGA')
                                ->options(function () {
                                    return \App\Models\VGA::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Pilih VGA'),
                            Select::make('details.psu_id')
                                ->label('PSU')
                                ->options(function () {
                                    return \App\Models\PSU::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Pilih PSU'),
                            Select::make('details.keyboard_id')
                                ->label('Keyboard')
                                ->options(function () {
                                    return \App\Models\Keyboard::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Pilih Keyboard'),
                            Select::make('details.mouse_id')
                                ->label('Mouse')
                                ->options(function () {
                                    return \App\Models\Mouse::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Pilih Mouse'),
                            Select::make('details.monitor_id')
                                ->label('Monitor')
                                ->options(function () {
                                    return \App\Models\Monitor::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->placeholder('Pilih Monitor'),
                            Select::make('details.dvd_id')
                                ->label('DVD (Optional)')
                                ->options(function () {
                                    return \App\Models\DVD::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->placeholder('Pilih DVD (Opsional)')
                                ->helperText('Pilih DVD jika PC memiliki drive DVD'),
                            Select::make('details.headphone_id')
                                ->label('Headphone (Optional)')
                                ->options(function () {
                                    return \App\Models\Headphone::all()->pluck('full_name', 'id');
                                })
                                ->searchable()
                                ->preload()
                                ->nullable()
                                ->placeholder('Pilih Headphone (Opsional)')
                                ->helperText('Pilih headphone jika diperlukan'),
                        ])
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode_pc')
                    ->label('Kode PC')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('no_pc')
                    ->label('NoPC')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Tables\Actions\Action::make('viewComponents')
                            ->modalHeading(fn($record) => "Detail Komponen PC - {$record->no_pc} ({$record->kode_pc})")
                            ->modalContent(fn($record) => view('filament.components.pc-detail-modal', ['record' => $record]))
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Tutup')
                    ),
                TextColumn::make('lokasi.ruang')
                    ->label('Lokasi')
                    ->sortable()
                    ->badge(),
                TextColumn::make('kondisi')
                    ->label('Kondisi')
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Baik' => 'success',
                        'Rusak Ringan' => 'warning',
                        'Rusak Berat' => 'danger',
                        'Dalam Perbaikan' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('asal.ruang')
                    ->label('Asal PC')
                    ->sortable()
                    ->placeholder('Pengadaan Baru'),
                TextColumn::make('updated_at')
                    ->label('Last Update')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('updatedBy.name')
                    ->label('Updated By')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Belum Tercatat'),
                
                // Existing columns as hidden by default
                TextColumn::make('kode_inventaris')
                    ->label('No Inventaris')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('processor_name')
                    ->label('CPU')
                    ->state(fn (Inventory $record) => $record->getComponentName('processor'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ram_name')
                    ->label('RAM')
                    ->state(fn (Inventory $record) => $record->getComponentName('ram'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('motherboard_name')
                    ->label('Motherboard')
                    ->state(fn (Inventory $record) => $record->getComponentName('motherboard'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('penyimpanan_name')
                    ->label('Storage')
                    ->state(fn (Inventory $record) => $record->getComponentName('penyimpanan'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('vga_name')
                    ->label('VGA')
                    ->state(fn (Inventory $record) => $record->getComponentName('vga'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('psu_name')
                    ->label('PSU')
                    ->state(fn (Inventory $record) => $record->getComponentName('psu'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('keyboard_name')
                    ->label('Keyboard')
                    ->state(fn (Inventory $record) => $record->getComponentName('keyboard'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('mouse_name')
                    ->label('Mouse')
                    ->state(fn (Inventory $record) => $record->getComponentName('mouse'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('monitor_name')
                    ->label('Monitor')
                    ->state(fn (Inventory $record) => $record->getComponentName('monitor'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('dvd_name')
                    ->label('DVD')
                    ->state(fn (Inventory $record) => $record->getComponentName('dvd'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('headphone_name')
                    ->label('Headphone')
                    ->state(fn (Inventory $record) => $record->getComponentName('headphone'))
                    ->toggleable(isToggledHiddenByDefault: true),
                    

            ])
            ->filters([
                SelectFilter::make('laboratorium')
                    ->relationship(
                        'laboratorium',
                        'ruang',
                        fn(Builder $query) => auth()->user()->hasRole('super_admin')
                        ? $query
                        : $query->whereIn('id', auth()->user()->getAuthorizedLabIds('view'))
                    )
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Lihat Inventaris PC')
                    ->infolist(fn(Infolist $infolist): Infolist => static::infolist($infolist)),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Inventory $record) {
                        // Hapus record detail terkait sebelum menghapus record inventaris utama
                        $record->inventoriable?->delete();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->before(function ($records) {
                        $records->each(fn(Inventory $record) => $record->inventoriable?->delete());
                    }),
                ]),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    // Arahkan action untuk memanggil metode 'exportToExcel' di Livewire Component (List Page)
                    ->action(fn($livewire) => $livewire->exportToExcel())
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Informasi Umum PC')
                    ->schema([
                        TextEntry::make('kode_pc')
                            ->label('Kode PC'),
                        TextEntry::make('no_pc')
                            ->label('No PC'),
                        TextEntry::make('lokasi.ruang')
                            ->label('Lokasi')
                            ->badge(),
                        TextEntry::make('asal.ruang')
                            ->label('Asal PC')
                            ->placeholder('Pengadaan Baru'),
                        TextEntry::make('kode_inventaris')
                            ->label('No Inventaris'),
                        TextEntry::make('tanggal_pengadaan')
                            ->label('Tanggal Pengadaan')
                            ->date('d M Y')
                            ->placeholder('-'),
                        TextEntry::make('kondisi')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'Baik' => 'success',
                                'Rusak Ringan' => 'warning',
                                'Rusak Berat' => 'danger',
                                'Dalam Perbaikan' => 'info',
                                default => 'gray',
                            }),
                    ])->columns(3),

                InfoSection::make('Spesifikasi Komponen PC')
                    ->description('Detail komponen hardware yang terpasang.')
                    ->schema([
                        InfoGrid::make(3)->schema([
                            TextEntry::make('processor_name')
                                ->label('Processor')
                                ->state(fn (Inventory $record) => $record->getComponentName('processor'))
                                ->placeholder('-'),
                            TextEntry::make('motherboard_name')
                                ->label('Motherboard')
                                ->state(fn (Inventory $record) => $record->getComponentName('motherboard'))
                                ->placeholder('-'),
                            TextEntry::make('ram_name')
                                ->label('RAM')
                                ->state(fn (Inventory $record) => $record->getComponentName('ram'))
                                ->placeholder('-'),
                            TextEntry::make('penyimpanan_name')
                                ->label('Penyimpanan')
                                ->state(fn (Inventory $record) => $record->getComponentName('penyimpanan'))
                                ->placeholder('-'),
                            TextEntry::make('vga_name')
                                ->label('VGA')
                                ->state(fn (Inventory $record) => $record->getComponentName('vga'))
                                ->placeholder('-'),
                            TextEntry::make('psu_name')
                                ->label('PSU')
                                ->state(fn (Inventory $record) => $record->getComponentName('psu'))
                                ->placeholder('-'),
                            TextEntry::make('keyboard_name')
                                ->label('Keyboard')
                                ->state(fn (Inventory $record) => $record->getComponentName('keyboard'))
                                ->placeholder('-'),
                            TextEntry::make('mouse_name')
                                ->label('Mouse')
                                ->state(fn (Inventory $record) => $record->getComponentName('mouse'))
                                ->placeholder('-'),
                            TextEntry::make('monitor_name')
                                ->label('Monitor')
                                ->state(fn (Inventory $record) => $record->getComponentName('monitor'))
                                ->placeholder('-'),
                            TextEntry::make('dvd_name')
                                ->label('DVD')
                                ->state(fn (Inventory $record) => $record->getComponentName('dvd'))
                                ->placeholder('Tidak ada'),
                            TextEntry::make('headphone_name')
                                ->label('Headphone')
                                ->state(fn (Inventory $record) => $record->getComponentName('headphone'))
                                ->placeholder('Tidak ada'),
                        ]),
                    ]),
                    
                InfoSection::make('Audit Trail')
                    ->description('Rekam jejak perubahan data inventaris.')
                    ->visible(fn () => auth()->user()->hasRole('super_admin'))
                    ->schema([
                        TextEntry::make('updated_at')
                            ->label('Terakhir Diubah Waktu')
                            ->dateTime('d M Y, H:i:s'),
                        TextEntry::make('updatedBy.name')
                            ->label('Terakhir Diubah Oleh')
                            ->placeholder('Belum Tercatat')
                            ->badge()
                            ->color('info'),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPCInventories::route('/'),
            'create' => Pages\CreatePCInventory::route('/create'),
            'edit' => Pages\EditPCInventory::route('/{record}/edit'),
        ];
    }
}
