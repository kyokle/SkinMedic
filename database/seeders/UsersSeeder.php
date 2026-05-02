<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // Doctor
            [
                'firstname'          => 'Cindy',
                'lastname'           => 'Hoseña',
                'email'              => 'doctor@skinmedic.com',
                'gender'             => 'female',
                'phone_no'           => '09123456789',
                'address'            => 'Imus, Cavite',
                'password_hash'      => password_hash('Doctor@123', PASSWORD_DEFAULT),
                'role'               => 'doctor',
                'email_verified_at'  => now(),
                'verify_token'       => null,
            ],
            // Staff
            [
                'firstname'          => 'Ana',
                'lastname'           => 'Asuncion',
                'email'              => 'staff@skinmedic.com',
                'gender'             => 'female',
                'phone_no'           => '09987654321',
                'address'            => 'Imus, Cavite',
                'password_hash'      => password_hash('Staff@123', PASSWORD_DEFAULT),
                'role'               => 'staff',
                'email_verified_at'  => now(),
                'verify_token'       => null,
            ],
            // Admin
            [
                'firstname'          => 'SkinMedic',
                'lastname'           => 'Admin',
                'email'              => 'admin@skinmedic.com',
                'gender'             => 'male',
                'phone_no'           => '09111111111',
                'address'            => 'Imus, Cavite',
                'password_hash'      => password_hash('Admin@123', PASSWORD_DEFAULT),
                'role'               => 'admin',
                'email_verified_at'  => now(),
                'verify_token'       => null,
            ],
        ];

        DB::table('users')->insert($users);
    }
}