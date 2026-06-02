<?php

namespace Database\Seeders;

use App\Models\Panel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Super Admin
        User::updateOrCreate(
            ['email' => 'admin@pesat.test'],
            [
                'name'     => 'Admin PESAT',
                'password' => Hash::make('password'),
                'role'     => 'super_admin',
            ]
        );

        // 6 Panel: RPL/DKV/TKJ × Kelas 10/11
        $panels = [
            ['name' => 'Panel RPL Kelas 10', 'grade' => '10', 'major' => 'RPL', 'operator_pin' => '1001', 'location' => 'Ruang Magnavox'],
            ['name' => 'Panel RPL Kelas 11', 'grade' => '11', 'major' => 'RPL', 'operator_pin' => '1101', 'location' => 'Ruang Vokal'],
            ['name' => 'Panel DKV Kelas 10', 'grade' => '10', 'major' => 'DKV', 'operator_pin' => '1002', 'location' => 'Studio'],
            ['name' => 'Panel DKV Kelas 11', 'grade' => '11', 'major' => 'DKV', 'operator_pin' => '1102', 'location' => 'Lab Kampus 1'],
            ['name' => 'Panel TKJ Kelas 10', 'grade' => '10', 'major' => 'TKJ', 'operator_pin' => '1003', 'location' => 'Aula Kiri'],
            ['name' => 'Panel TKJ Kelas 11', 'grade' => '11', 'major' => 'TKJ', 'operator_pin' => '1103', 'location' => 'Aula Kanan'],
        ];

        foreach ($panels as $panelData) {
            Panel::updateOrCreate(
                ['name' => $panelData['name']],
                array_merge($panelData, ['status' => 'inactive'])
            );
        }
    }
}

