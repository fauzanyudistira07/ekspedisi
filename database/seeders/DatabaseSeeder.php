<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $branch = Branch::firstOrCreate(
            ['name' => 'Pusat Jakarta'],
            [
                'city' => 'Jakarta',
                'address' => 'Jl. Sudirman No. 1',
                'phone' => '0210000000',
            ],
        );

        $defaultPassword = Hash::make('password123');

        User::updateOrCreate([
            'email' => 'admin@ekspedisi.test',
        ], [
            'name' => 'Admin',
            'password' => $defaultPassword,
            'role' => User::ROLE_ADMIN,
            'branch_id' => $branch->id,
        ]);

        User::updateOrCreate([
            'email' => 'manager@ekspedisi.test',
        ], [
            'name' => 'Manager',
            'password' => $defaultPassword,
            'role' => User::ROLE_MANAGER,
            'branch_id' => $branch->id,
        ]);

        User::updateOrCreate([
            'email' => 'cashier@ekspedisi.test',
        ], [
            'name' => 'Cashier',
            'password' => $defaultPassword,
            'role' => User::ROLE_CASHIER,
            'branch_id' => $branch->id,
        ]);

        User::updateOrCreate([
            'email' => 'courier@ekspedisi.test',
        ], [
            'name' => 'Courier',
            'password' => $defaultPassword,
            'role' => User::ROLE_COURIER,
            'branch_id' => $branch->id,
        ]);

        Customer::updateOrCreate([
            'email' => 'customer@ekspedisi.test',
        ], [
            'name' => 'Customer Demo',
            'password' => $defaultPassword,
            'address' => 'Jl. Kemang Raya No. 10',
            'city' => 'Jakarta',
            'phone' => '081200000000',
        ]);
    }
}
