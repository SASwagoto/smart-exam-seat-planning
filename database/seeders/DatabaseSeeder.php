<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->call('shield:generate', ['--all' => true, '--no-interaction' => true]);

        // ২. ইউজার তৈরি করুন
        $user = \App\Models\User::updateOrCreate(
            ['email' => 'admin@smartexa.com'],
            [
                'name' => 'Shawon Ahmed',
                'password' => bcrypt('password'),
            ]
        );

        // ৩. সুপার অ্যাডমিন রোল এসাইন করুন
        // shield:generate কমান্ডটি অটোমেটিক 'super_admin' রোল তৈরি করে রাখে
        $user->assignRole('super_admin');

        $this->command->info('Admin user created successfully!');
    }
}
