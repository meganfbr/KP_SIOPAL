<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Admin
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@mail.com'],
            [
                'name' => 'Super Administrator',
                'npp' => 'A11.2022.2022',
                'no_phone' => '081234567890',
                'password' => Hash::make('superadmin'),
                'tanggal_masuk' => '2020-01-01',
                'position' => 'Kepala Laboratorium',
                'is_active' => true,
            ]
        );
        $superAdmin->assignRole('super_admin');

        $labs = [
            'D2A', 'D2B', 'D2C', 'D2D', 'D2E', 'D2F', 'D2G',
            'D2H', 'D2I', 'D2J', 'D2K', 'D3L', 'D3M', 'D3N'
        ];
        $shifts = ['pagi', 'siang'];

        $userRows = [
            ['Super Administrator', 'superadmin@mail.com', 'A11.2022.2022', 'super_admin', 'superadmin'],
        ];

        foreach ($labs as $labSlug) {
            foreach ($shifts as $shift) {
                $labSlugLower = strtolower($labSlug);
                $email = "laboran_{$labSlugLower}_{$shift}@mail.com";
                $passwordPlain = "lab-{$labSlugLower}-{$shift}";
                $npp = "LAB{$labSlugLower}.{$shift}.2026";
                $name = "Laboran Lab " . strtoupper($labSlug) . " " . ucfirst($shift);
                $labRole = 'Laboran_' . strtoupper($labSlug);

                $user = User::updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'npp' => $npp,
                        'no_phone' => '081234567890',
                        'password' => Hash::make($passwordPlain),
                        'tanggal_masuk' => '2026-01-01',
                        'position' => 'Laboran',
                        'is_active' => true,
                    ]
                );

                $user->syncRoles([$labRole]);
                $user->givePermissionTo($this->getLabPermissions($labSlug));

                $userRows[] = [
                    $user->name,
                    $email,
                    $user->npp,
                    $labRole,
                    $passwordPlain,
                ];
            }
        }

        $this->command->info('Users seeded successfully!');
        $this->command->table(
            ['Name', 'Email', 'NPP', 'Role', 'Password'],
            $userRows
        );
    }

    protected function getLabPermissions(string $labSlug): array
    {
        $labSlug = strtolower($labSlug);

        $permissions = [
            "lab_{$labSlug}_view",
            "lab_{$labSlug}_manage",
            "lab_{$labSlug}_edit",
            "lab_{$labSlug}_delete",
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        return $permissions;
    }
}
