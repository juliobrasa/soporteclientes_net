<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador principal
        User::firstOrCreate(
            ['email' => 'admin@kavia.com'],
            [
                'name' => 'Admin Kavia',
                'email' => 'admin@kavia.com',
                'password' => Hash::make('admin123'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        // Crear usuario de prueba
        User::firstOrCreate(
            ['email' => 'test@kavia.com'],
            [
                'name' => 'Test User',
                'email' => 'test@kavia.com',
                'password' => Hash::make('test123'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('✅ Usuarios administradores creados:');
        $this->command->info('   - admin@kavia.com (admin123)');
        $this->command->info('   - test@kavia.com (test123)');
    }
}
