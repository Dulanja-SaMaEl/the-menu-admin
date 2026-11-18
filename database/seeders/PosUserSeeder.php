<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PosUserSeeder extends Seeder
{
    /**
     * Seed the POS Terminal User.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'POS'],
            [
                'name' => 'POS Counter Terminal',
                'email' => 'pos@themenu.com',
                'role' => 'POS',
                'password' => Hash::make('Pos@9454'),
            ]
        );
    }
}
