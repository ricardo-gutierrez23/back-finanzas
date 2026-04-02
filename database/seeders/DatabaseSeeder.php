<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@finanzas.com'],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'password' => '$2y$12$hXP7iYHg4eSwZgZDaXGE9.lh8nP/RSpEjCeVN2U9chZIMU7pKuEje',
                'role' => 'Admin',
            ]
        );
    }
}
