<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\HasLabPermissions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasLabPermissions, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'npp', 'no_phone', 'posisi', 'tanggal_masuk', 'tanggal_keluar'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "User telah di-{$eventName}")
            ->useLogName('user');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'no_phone',
        'npp',
        'posisi',
        'foto',
        'tanggal_masuk',
        'tanggal_keluar',
        'is_active',
    ];

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->tanggal_keluar && $this->tanggal_keluar->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'npp';
    }
}
