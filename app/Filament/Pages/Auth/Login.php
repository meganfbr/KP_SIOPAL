<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

/**
 * Custom Login Page untuk SIOPAL
 * Menggunakan NPP/NIM sebagai credential utama, bukan email
 */
class Login extends BaseLogin
{
    /**
     * Override form untuk menggunakan NPP sebagai login field
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNppFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    /**
     * NPP/NIM Form Component (menggantikan email)
     */
    protected function getNppFormComponent(): Component
    {
        return TextInput::make('npp')
            ->label('NPP/NIM')
            ->required()
            ->autocomplete('username')
            ->autofocus()
            ->extraInputAttributes(['tabindex' => 1]);
    }

    /**
     * Password Form Component
     */
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::pages/auth/login.form.password.label'))
            ->hint(filament()->hasPasswordReset() ? new \Illuminate\Support\HtmlString(
                \Filament\Support\Facades\FilamentView::renderHook('panels::auth.login.password-hint')
            ) : null)
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->autocomplete('current-password')
            ->required()
            ->extraInputAttributes(['tabindex' => 2]);
    }

    /**
     * Remember Me Checkbox
     */
    protected function getRememberFormComponent(): Component
    {
        return Checkbox::make('remember')
            ->label(__('filament-panels::pages/auth/login.form.remember.label'));
    }

    /**
     * Override authenticate untuk menggunakan NPP
     */
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/login.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/login.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/login.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        // Authenticate menggunakan NPP sebagai pengganti email
        if (
            !Filament::auth()->attempt([
                'npp' => $data['npp'],
                'password' => $data['password'],
            ], $data['remember'] ?? false)
        ) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (!$user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            if (!$user->is_active || ($user->tanggal_keluar && $user->tanggal_keluar->isPast())) {
                throw ValidationException::withMessages([
                    'data.npp' => 'Akun Anda tidak aktif atau masa kontrak telah berakhir.',
                ]);
            }

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    /**
     * Override credentials untuk NPP
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'npp' => $data['npp'],
            'password' => $data['password'],
        ];
    }

    /**
     * Override validation exception untuk NPP
     */
    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages([
            'data.npp' => __('filament-panels::pages/auth/login.messages.failed'),
        ]);
    }
}
