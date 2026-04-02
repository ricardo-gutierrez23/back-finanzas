<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-test-user')]
#[Description('Command description')]
class CreateTestUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Illuminate\Support\Facades\DB::insert('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)', [
            'Admin Ricardo',
            'admin@finanzas.com',
            \Illuminate\Support\Facades\Hash::make('12345678'),
            'Admin'
        ]);

        $this->info('Usuario admin@finanzas.com creado con contraseña 12345678');
    }
}
