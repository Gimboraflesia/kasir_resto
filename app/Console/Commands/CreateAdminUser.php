<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin';

    protected $description = 'Create the first administrator account securely';

    public function handle(): int
    {
        $name = $this->ask('Nama administrator');
        $email = $this->ask('Email administrator');
        $password = $this->secret('Password administrator');

        if (! is_string($name) || trim($name) === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! is_string($password) || strlen($password) < 8) {
            $this->components->error('Nama dan email harus valid; password minimal 8 karakter.');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->components->error('Email tersebut sudah digunakan.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => trim($name),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'admin',
        ]);

        $this->components->info('Akun administrator berhasil dibuat.');

        return self::SUCCESS;
    }
}
