<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use App\Filament\Resources\BarangKeluarResource;
use App\Filament\Resources\BarangMasukResource;
use App\Filament\Resources\PCInventoryResource;
use App\Filament\Resources\NonPCInventoryResource;
use App\Filament\Resources\SoftwareInventoryResource;
use App\Filament\Resources\ProdiResource;
use App\Filament\Resources\LecturerResource;
use App\Filament\Resources\CourseResource;
use App\Filament\Pages\ScheduleTimetable;
use App\Filament\Widgets\CalendarWidget;
use App\Filament\Widgets\KalenderAkademikWidget;
use App\Filament\Widgets\StatsOverviewWidget;
use App\Filament\Widgets\WelcomeWidget;
use App\Models\Laboratorium;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // ->spa() // Disabled SPA mode to prevent CSRF issues
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->colors([
                'primary' => '#104b8f',
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('3s')
            ->favicon(url('images/udinus.png'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class, // Menggunakan Dashboard kustom kita
            ])
            ->widgets([
                WelcomeWidget::class,
                StatsOverviewWidget::class,
                KalenderAkademikWidget::class,
                CalendarWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
            ])

            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                // Hanya lanjutkan jika pengguna sudah login
                if (!auth()->check()) {
                    return $builder;
                }

                $user = auth()->user();
                $navigationGroups = [];

                // Helper untuk mengecek apakah user adalah salah satu role laboran
                $isLaboran = $user->roles->pluck('name')->contains(function ($name) {
                    return str_starts_with($name, 'Laboran_');
                });

                // Menu Utama (Dashboard) - selalu tampilkan untuk semua user
                $navigationGroups[] = NavigationGroup::make('Menu Utama')
                    ->items([
                        NavigationItem::make('Dashboard')
                            ->icon('heroicon-o-home')
                            ->url(fn() => Dashboard::getUrl())
                            ->isActiveWhen(fn() => request()->routeIs('filament.admin.pages.dashboard')),
                    ]);

                // PENJADWALAN - tampilkan untuk semua yang memiliki izin terkait penjadwalan
                $penjadwalanItems = [];

                // Program Studi
                if ($user->hasRole('super_admin') || $user->can('view-navigation-item', 'prodi')) {
                    $penjadwalanItems[] = NavigationItem::make('Program Studi')
                        ->icon('heroicon-o-academic-cap')
                        ->url(\App\Filament\Resources\ProdiResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\ProdiResource::getRouteBaseName() . '.*'));
                }

                // Dosen
                if ($user->hasRole('super_admin') || $user->can('view-navigation-item', 'lecturer')) {
                    $penjadwalanItems[] = NavigationItem::make('Dosen')
                        ->icon('heroicon-o-user-group')
                        ->url(\App\Filament\Resources\LecturerResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\LecturerResource::getRouteBaseName() . '.*'));
                }

                // Mata Kuliah
                if ($user->hasRole('super_admin') || $user->can('view-navigation-item', 'course')) {
                    $penjadwalanItems[] = NavigationItem::make('Mata Kuliah')
                        ->icon('heroicon-o-book-open')
                        ->url(\App\Filament\Resources\CourseResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\CourseResource::getRouteBaseName() . '.*'));
                }

                // Penjadwalan Otomatis (Schedule Wizard) - NEW!
                if ($user->hasRole('super_admin') || $user->can('page_ScheduleWizard')) {
                    $penjadwalanItems[] = NavigationItem::make('Penjadwalan Otomatis')
                        ->icon('heroicon-o-sparkles')
                        ->url('/admin/schedule-wizard')
                        ->isActiveWhen(fn() => request()->is('admin/schedule-wizard*'));
                }

                // Jadwal Kuliah (ScheduleResource) - untuk melihat/edit manual
                if ($user->hasRole('super_admin') || $isLaboran || $user->can('view_any_schedule')) {
                    $penjadwalanItems[] = NavigationItem::make('Jadwal Kuliah')
                        ->icon('heroicon-o-calendar-days')
                        ->url(\App\Filament\Resources\ScheduleResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\ScheduleResource::getRouteBaseName() . '.*'));
                }

                // Timetable Visual (visualisasi jadwal)
                if ($user->hasRole('super_admin') || $user->can('page_ScheduleTimetable')) {
                    $penjadwalanItems[] = NavigationItem::make('Timetable Visual')
                        ->icon('heroicon-o-table-cells')
                        ->url(\App\Filament\Pages\ScheduleTimetable::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs('filament.admin.pages.schedule-timetable'));
                }

                // Tambahkan grup PENJADWALAN jika ada item di dalamnya
                if (count($penjadwalanItems) > 0) {
                    $navigationGroups[] = NavigationGroup::make('Penjadwalan')
                        ->items($penjadwalanItems);
                }

                // Pelaporan PTPP - tampilkan untuk semua yang memiliki izin terkait PTPP
                if ($user->hasRole('super_admin') || $isLaboran || $user->can('view-navigation-item', 'lapor::ptpp')) {
                    $navigationGroups[] = NavigationGroup::make('Pelaporan PTPP')
                        ->items([
                            NavigationItem::make('PTTP SKT')
                                ->icon('heroicon-o-document-text')
                                ->url(\App\Filament\Resources\LaporPtppResource::getUrl())
                                ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\LaporPtppResource::getRouteBaseName() . '.*')),
                        ]);
                }

                // MASTER DATA - hanya tampilkan jika user memiliki izin
                $masterDataItems = [];

                // Data Laboran
                if ($user->hasRole('super_admin') || $user->can('view-navigation-item', 'user')) {
                    $masterDataItems[] = NavigationItem::make('Data Laboran')
                        ->icon('heroicon-o-users')
                        ->url(\App\Filament\Resources\UserResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\UserResource::getRouteBaseName() . '.*'));
                }


                // Data Laboratorium
                if ($user->hasRole('super_admin') || $isLaboran || $user->can('view-navigation-item', 'laboratorium')) {
                    $masterDataItems[] = NavigationItem::make('Data Laboratorium')
                        ->icon('heroicon-o-building-office')
                        ->url(\App\Filament\Resources\LaboratoriumResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\LaboratoriumResource::getRouteBaseName() . '.*'));
                }

                // Data Klasifikasi Lab
                if ($user->hasRole('super_admin') || $isLaboran || $user->can('view-navigation-item', 'klasifikasi::lab')) {
                    $masterDataItems[] = NavigationItem::make('Data Klasifikasi Lab')
                        ->icon('heroicon-o-computer-desktop')
                        ->url(\App\Filament\Resources\KlasifikasiLabResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\KlasifikasiLabResource::getRouteBaseName() . '.*'));
                }

                // Daftar Software
                if ($user->hasRole('super_admin') || $isLaboran || $user->can('view_any_software')) {
                    $masterDataItems[] = NavigationItem::make('Daftar Software')
                        ->icon('heroicon-o-puzzle-piece')
                        ->url(fn() => '/admin/software')
                        ->isActiveWhen(fn() => request()->routeIs('filament.admin.resources.software.*'));
                }

                // Rekap Inventaris
                if ($user->hasRole('super_admin') || $isLaboran) {
                    $masterDataItems[] = NavigationItem::make('Rekap Inventaris')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->url(fn() => \App\Filament\Pages\RekapInventaris::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs('filament.admin.pages.rekap-inventaris'));

                    // Inventaris PC (Super Admin & Laboran)
                    if ($user->hasRole('super_admin') || $isLaboran) {
                        $masterDataItems[] = NavigationItem::make('Inventaris PC')
                            ->icon('heroicon-o-computer-desktop')
                            ->url(fn() => PCInventoryResource::getUrl('index'))
                            ->isActiveWhen(fn() => request()->routeIs(PCInventoryResource::getRouteBaseName() . '.*'));
                    }

                    // Inventaris Non-PC & Software (Superadmin Only)
                    if ($user->hasRole('super_admin')) {
                        // Inventaris Non-PC
                        $masterDataItems[] = NavigationItem::make('Inventaris Non-PC')
                            ->icon('heroicon-o-cpu-chip')
                            ->url(fn() => NonPCInventoryResource::getUrl('index'))
                            ->isActiveWhen(fn() => request()->routeIs(NonPCInventoryResource::getRouteBaseName() . '.*'));

                        // Inventaris Software
                        $masterDataItems[] = NavigationItem::make('Inventaris Software')
                            ->icon('heroicon-o-code-bracket-square')
                            ->url(fn() => SoftwareInventoryResource::getUrl('index'))
                            ->isActiveWhen(fn() => request()->routeIs(SoftwareInventoryResource::getRouteBaseName() . '.*'));
                    }

                    // Barang Masuk
                    $masterDataItems[] = NavigationItem::make('Barang Masuk')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn() => BarangMasukResource::getUrl('index'))
                        ->isActiveWhen(fn() => request()->routeIs(BarangMasukResource::getRouteBaseName() . '.*'));

                    // Barang Keluar
                    $masterDataItems[] = NavigationItem::make('Barang Keluar')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->url(fn() => BarangKeluarResource::getUrl('index'))
                        ->isActiveWhen(fn() => request()->routeIs(BarangKeluarResource::getRouteBaseName() . '.*'));
                }
            

                // Permissions
                if ($user->hasRole('super_admin') || $user->can('view-navigation-item', 'role')) {
                    $masterDataItems[] = NavigationItem::make('Permissions')
                        ->icon('heroicon-o-shield-check')
                        ->url(fn() => route('filament.admin.resources.shield.roles.index'))
                        ->isActiveWhen(fn() => request()->routeIs('filament.admin.resources.shield.roles.*'));
                }



                // Tambahkan grup MASTER DATA jika ada item di dalamnya
                if (count($masterDataItems) > 0) {
                    $navigationGroups[] = NavigationGroup::make('MASTER DATA')
                        ->items($masterDataItems);
                }

                // DATA HARDWARE - hanya tampilkan jika user memiliki izin
                $hardwareItems = [];

                // Motherboard
                if (\App\Filament\Resources\MotherboardResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('Motherboard')
                        ->icon('heroicon-o-cpu-chip')
                        ->url(\App\Filament\Resources\MotherboardResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\MotherboardResource::getRouteBaseName() . '.*'));
                }

                // Processor
                if (\App\Filament\Resources\ProcessorResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('Processor')
                        ->icon('heroicon-o-cpu-chip')
                        ->url(\App\Filament\Resources\ProcessorResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\ProcessorResource::getRouteBaseName() . '.*'));
                }

                // RAM
                if (\App\Filament\Resources\RAMResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('RAM')
                        ->icon('heroicon-o-server-stack')
                        ->url(\App\Filament\Resources\RAMResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\RAMResource::getRouteBaseName() . '.*'));
                }

                // VGA
                if (\App\Filament\Resources\VGAResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('VGA')
                        ->icon('heroicon-o-chart-bar')
                        ->url(\App\Filament\Resources\VGAResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\VGAResource::getRouteBaseName() . '.*'));
                }

                // Penyimpanan
                if (\App\Filament\Resources\PenyimpananResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('Penyimpanan')
                        ->icon('heroicon-o-circle-stack')
                        ->url(\App\Filament\Resources\PenyimpananResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\PenyimpananResource::getRouteBaseName() . '.*'));
                }

                // DVD
                if (\App\Filament\Resources\DVDResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('DVD')
                        ->icon('heroicon-o-document')
                        ->url(\App\Filament\Resources\DVDResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\DVDResource::getRouteBaseName() . '.*'));
                }

                // PSU
                if (\App\Filament\Resources\PSUResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('PSU')
                        ->icon('heroicon-o-cube')
                        ->url(\App\Filament\Resources\PSUResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\PSUResource::getRouteBaseName() . '.*'));
                }

                // Keyboard
                if (\App\Filament\Resources\KeyboardResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('Keyboard')
                        ->icon('heroicon-o-command-line')
                        ->url(\App\Filament\Resources\KeyboardResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\KeyboardResource::getRouteBaseName() . '.*'));
                }

                // Mouse
                if (\App\Filament\Resources\MouseResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('Mouse')
                        ->icon('heroicon-o-cursor-arrow-rays')
                        ->url(\App\Filament\Resources\MouseResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\MouseResource::getRouteBaseName() . '.*'));
                }

                // Monitor
                if (\App\Filament\Resources\MonitorResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('Monitor')
                        ->icon('heroicon-o-tv')
                        ->url(\App\Filament\Resources\MonitorResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\MonitorResource::getRouteBaseName() . '.*'));
                }

                // Headphone
                if (\App\Filament\Resources\HeadphoneResource::shouldRegisterNavigation()) {
                    $hardwareItems[] = NavigationItem::make('Headphone')
                        ->icon('heroicon-o-speaker-wave')
                        ->url(\App\Filament\Resources\HeadphoneResource::getUrl())
                        ->isActiveWhen(fn() => request()->routeIs(\App\Filament\Resources\HeadphoneResource::getRouteBaseName() . '.*'));
                }

                // Tambahkan grup Data Hardware jika ada item di dalamnya
                if (count($hardwareItems) > 0) {
                    $navigationGroups[] = NavigationGroup::make('Data Hardware')
                        ->items($hardwareItems);
                }



                return $builder->groups($navigationGroups);
            });
    }
}
