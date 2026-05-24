<?php

namespace App\Filament\Resources;

use App\Filament\Clusters\AllHardware;
use App\Filament\Resources\KeyboardResource\Pages;
use App\Filament\Resources\KeyboardResource\RelationManagers;
use App\Models\Keyboard;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class KeyboardResource extends Resource
{
    public static function canCreate(): bool
    {
        return !auth()->user()->hasRole('super_admin');
    }

    public static function canEdit(Model $record): bool
    {
        return !auth()->user()->hasRole('super_admin');
    }

    public static function canDelete(Model $record): bool
    {
        return !auth()->user()->hasRole('super_admin');
    }

    public static function canDeleteAny(): bool
    {
        return !auth()->user()->hasRole('super_admin');
    }

    protected static ?string $model = Keyboard::class;

    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $slug = 'keyboard';

    protected static ?string $navigationLabel = 'Keyboard';

    protected static ?string $modelLabel = 'Keyboard';

    protected static ?string $navigationGroup = 'Data Hardware';

    // protected static ?string $cluster = AllHardware::class;

    protected static ?int $navigationSort = 8 ;


    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('no_inventaris')
                    ->label('No Inventaris')
                    ->disabled() // Dibuat otomatis di model
                    ->dehydrated(false), // Tidak dikirim ke backend

                TextInput::make('merk')
                    ->label('Merk')
                    ->required()
                    ->maxLength(255),

                Select::make('tipe')
                    ->label('Tipe Keyboard')
                    ->options([
                        'USB' => 'USB',
                        'Wireless' => 'Wireless',
                    ])
                    ->required(),

                Select::make('tahun')
                    ->label('Tahun')
                    ->options(function () {
                        $tahunSekarang = date('Y');
                        return array_combine(
                            range($tahunSekarang, $tahunSekarang - 20),
                            range($tahunSekarang, $tahunSekarang - 20)
                        );
                    })
                    ->required(),

                    Select::make('bulan')
                    ->label('Bulan Pengadaan')
                    ->options([
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                    ]),
                TextInput::make('stok')
                    ->label('Stok')
                    ->required()
                    ->minValue(0)
                    ->default(0)
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_inventaris')
                    ->label('No Inventaris')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('merk')
                    ->label('Merk')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('tipe')
                    ->label('Tipe Keyboard')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'USB' => 'success',
                        'Wireless' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),
                
                TextColumn::make('bulan')
                    ->label('Bulan Pengadaan')
                    ->formatStateUsing(function (?string $state): ?string {
                        if (empty($state)) {
                            return null;
                        }
                        $months = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        return $months[(int)$state] ?? $state;
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tahun')
                    ->label('Tahun')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('stok')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                SelectFilter::make('tipe')
                    ->label('Filter Tipe')
                    ->options([
                        'USB' => 'USB',
                        'Wireless' => 'Wireless',
                    ]),
                SelectFilter::make('tahun')
                    ->label('Filter Tahun')
                    ->options(function () {
                        $tahunSekarang = date('Y');
                        return array_combine(
                            range($tahunSekarang, $tahunSekarang - 20),
                            range($tahunSekarang, $tahunSekarang - 20)
                        );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKeyboards::route('/'),
            'create' => Pages\CreateKeyboard::route('/create'),
            'edit' => Pages\EditKeyboard::route('/{record}/edit'),
        ];
    }
}