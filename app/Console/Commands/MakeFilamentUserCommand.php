<?php

namespace App\Console\Commands;

use Filament\Commands\MakeUserCommand;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:filament-user', aliases: [
    'filament:make-user',
    'filament:user',
])]
class MakeFilamentUserCommand extends MakeUserCommand
{
    /**
     * @return array<InputOption>
     */
    protected function getOptions(): array
    {
        return [
            ...parent::getOptions(),
            new InputOption(
                name: 'username',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'Unique login username',
            ),
        ];
    }

    /**
     * @return array{name: string, username: string, email: ?string, password: string}
     */
    protected function getUserData(): array
    {
        $plainPassword = $this->options['password'] ?? password(
            label: 'Password',
            required: true,
        );

        return [
            'name' => $this->options['name'] ?? text(
                label: 'Name',
                required: true,
            ),

            'username' => $this->options['username'] ?? text(
                label: 'Username',
                required: true,
                validate: fn (string $username): ?string => match (true) {
                    strlen($username) < 3 => 'Username minimal 3 karakter.',
                    ! preg_match('/^[a-zA-Z0-9._-]+$/', $username) => 'Hanya huruf, angka, titik, strip, dan underscore.',
                    static::getUserModel()::query()->where('username', $username)->exists() => 'Username sudah dipakai.',
                    default => null,
                },
            ),

            'email' => $this->normalizeOptionalEmail($this->options['email'] ?? text(
                label: 'Email address (opsional)',
                required: false,
                validate: fn (?string $email): ?string => $this->validateOptionalEmail($email),
            )),

            'password' => Hash::make($plainPassword),
        ];
    }

    private function normalizeOptionalEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return $email;
    }

    private function validateOptionalEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Alamat email tidak valid.';
        }

        if (static::getUserModel()::query()->where('email', $email)->exists()) {
            return 'Email sudah terdaftar.';
        }

        return null;
    }
}
