<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $slug = 'laboran';

    protected static ?string $navigationLabel = 'Data Laboran';

    protected static ?string $modelLabel = 'Laboran';

    protected static ?string $navigationGroup = 'MASTER DATA';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $operation): bool => $operation === 'create'),
                TextInput::make('npp')
                    ->label('NPP/NIM')
                    ->required()
                    ->maxLength(50),
                TextInput::make('no_phone')
                    ->label('No HP')
                    ->required()
                    ->maxLength(15),
                Select::make('roles')
                    ->label('Role')
                    ->multiple()
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable(),
                FileUpload::make('foto')
                    ->label('Foto Laboran')
                    ->image()
                    ->directory('laboran-photos') // Simpan foto di storage/app/public/laboran-photos
                    ->nullable()
                    ->preserveFilenames()
                    ->columnSpanFull(),
                DatePicker::make('tanggal_masuk')
                    ->label('Tanggal Masuk')
                    ->native(false)
                    ->nullable(),
                DatePicker::make('tanggal_keluar')
                    ->label('Tanggal Keluar')
                    ->native(false)
                    ->nullable(),
                \Filament\Forms\Components\Toggle::make('is_active')
                    ->label('Status Aktif')
                    ->default(true)
                    ->helperText('Nonaktifkan untuk memblokir akses login user ini.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('npp')
                    ->label('NPP/NIM')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->color('primary')
                    ->searchable(),
                Tables\Columns\TextColumn::make('no_phone')
                    ->label('No HP')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tanggal_masuk')
                    ->label('Tanggal Masuk')
                    ->date()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                ImageColumn::make('foto')
                    ->circular(),
            ])
            ->filters([
                // Filter untuk semester (ganjil/genap)
                Filter::make('semester')
                    ->form([
                        Select::make('semester_type')
                            ->label('Tipe Semester')
                            ->options([
                                'odd' => 'Ganjil (September-Februari)',
                                'even' => 'Genap (Maret-Agustus)',
                            ]),
                        Select::make('tahun_akademik')
                            ->label('Tahun Akademik')
                            ->options(function () {
                                $years = [];
                                for ($year = 2020; $year <= 2030; $year++) {
                                    $nextYear = $year + 1;
                                    $years["$year-$nextYear"] = "$year/$nextYear";
                                }
                                return $years;
                            }),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['semester_type'] && $data['tahun_akademik'],
                                function (Builder $query) use ($data) {
                                    // Parse tahun akademik
                                    $years = explode('-', $data['tahun_akademik']);
                                    $startYear = (int)$years[0];
                                    $endYear = (int)$years[1];

                                    if ($data['semester_type'] === 'odd') {
                                        // Semester ganjil: September-Februari (tahun berikutnya)
                                        $startDate = "$startYear-09-01"; // 1 September
                                        $endDate = "$endYear-02-28";     // 28/29 Februari tahun berikutnya
                                    } else {
                                        // Semester genap: Maret-Agustus
                                        $startDate = "$startYear-03-01"; // 1 Maret
                                        $endDate = "$startYear-08-31";   // 31 Agustus
                                    }

                                    return $query->whereDate('tanggal_masuk', '>=', $startDate)
                                                ->whereDate('tanggal_masuk', '<=', $endDate);
                                }
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['semester_type'] ?? null) {
                            $semesterText = $data['semester_type'] === 'odd' ? 'Ganjil' : 'Genap';
                            $indicators['semester_type'] = "Semester: $semesterText";
                        }

                        if ($data['tahun_akademik'] ?? null) {
                            $years = explode('-', $data['tahun_akademik']);
                            $academicYear = $years[0] . '/' . $years[1];
                            $indicators['tahun_akademik'] = "Tahun Akademik: $academicYear";
                        }

                        return $indicators;
                    }),

                // Filter by role
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->preload()
                    ->searchable()
                    ->multiple()
                    ->label('Filter by Role'),
                
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->boolean()
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('gray')
                        ->icon('heroicon-o-eye'),
                    Tables\Actions\EditAction::make()
                        ->color('primary')
                        ->icon('heroicon-o-pencil'),
                    Tables\Actions\Action::make('toggleActive')
                        ->label(fn ($record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);
                            \Filament\Notifications\Notification::make()
                                ->title('Status berhasil diubah')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // Hapus action delete untuk mengamankan data
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }
}
