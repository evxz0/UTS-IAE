<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'              => 'Admin POS',
                'email'             => 'admin@pos.com',
                'password'          => Hash::make('password123'),
                'role'              => 'admin',
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Owner POS',
                'email'             => 'owner@pos.com',
                'password'          => Hash::make('password123'),
                'role'              => 'owner',
                'email_verified_at' => now(),
            ],
            [
                'name'              => 'Kasir POS',
                'email'             => 'kasir@pos.com',
                'password'          => Hash::make('password123'),
                'role'              => 'kasir',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['email' => $userData['email']],
                $userData
            );
        }

        $this->command->info('Users created successfully!');
        $this->command->table(
            ['Name', 'Email', 'Password', 'Role'],
            [
                ['Admin POS', 'admin@pos.com', 'password123', 'admin'],
                ['Owner POS', 'owner@pos.com', 'password123', 'owner'],
                ['Kasir POS', 'kasir@pos.com', 'password123', 'kasir'],
            ]
        );
    }
}
