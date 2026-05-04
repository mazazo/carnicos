<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'mario@mario.com'],
            [
                'name' => 'Mario',
                'last_name' => null,
                'movil' => '+59170000000',
                'password' => Hash::make('Ne42212296'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Asegura que aunque el usuario ya existiera quede como admin
        $user->update(['is_admin' => true]);

        $this->command->info("Usuario {$user->email} marcado como admin.");
    }
}