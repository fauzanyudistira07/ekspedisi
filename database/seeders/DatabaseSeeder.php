<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Rate;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $branchDefinitions = [
            'jakarta' => [
                'name' => 'Pusat Jakarta',
                'city' => 'Jakarta',
                'address' => 'Jl. Jenderal Sudirman No. 1, Jakarta Pusat',
                'phone' => '0210000000',
            ],
            'bandung' => [
                'name' => 'Cabang Bandung',
                'city' => 'Bandung',
                'address' => 'Jl. Asia Afrika No. 15, Bandung',
                'phone' => '0220000000',
            ],
            'semarang' => [
                'name' => 'Cabang Semarang',
                'city' => 'Semarang',
                'address' => 'Jl. Pandanaran No. 88, Semarang',
                'phone' => '0240000000',
            ],
            'yogyakarta' => [
                'name' => 'Cabang Yogyakarta',
                'city' => 'Yogyakarta',
                'address' => 'Jl. Malioboro No. 25, Yogyakarta',
                'phone' => '02740000000',
            ],
            'surabaya' => [
                'name' => 'Cabang Surabaya',
                'city' => 'Surabaya',
                'address' => 'Jl. Basuki Rahmat No. 21, Surabaya',
                'phone' => '0310000000',
            ],
        ];

        $branches = collect($branchDefinitions)->mapWithKeys(function (array $branch, string $key) {
            return [$key => Branch::updateOrCreate(['name' => $branch['name']], $branch)];
        });

        $baseRouteDefinitions = [
            ['origin' => 'Jakarta', 'destination' => 'Bandung', 'price_per_kg' => 12000, 'estimated_days' => 2],
            ['origin' => 'Jakarta', 'destination' => 'Semarang', 'price_per_kg' => 14000, 'estimated_days' => 2],
            ['origin' => 'Jakarta', 'destination' => 'Yogyakarta', 'price_per_kg' => 15000, 'estimated_days' => 2],
            ['origin' => 'Jakarta', 'destination' => 'Surabaya', 'price_per_kg' => 18000, 'estimated_days' => 3],
            ['origin' => 'Bandung', 'destination' => 'Semarang', 'price_per_kg' => 13000, 'estimated_days' => 2],
            ['origin' => 'Bandung', 'destination' => 'Yogyakarta', 'price_per_kg' => 13500, 'estimated_days' => 2],
            ['origin' => 'Bandung', 'destination' => 'Surabaya', 'price_per_kg' => 17500, 'estimated_days' => 3],
            ['origin' => 'Semarang', 'destination' => 'Yogyakarta', 'price_per_kg' => 11000, 'estimated_days' => 1],
            ['origin' => 'Semarang', 'destination' => 'Surabaya', 'price_per_kg' => 14500, 'estimated_days' => 2],
            ['origin' => 'Yogyakarta', 'destination' => 'Surabaya', 'price_per_kg' => 15500, 'estimated_days' => 2],
        ];

        foreach ($baseRouteDefinitions as $route) {
            Rate::updateOrCreate(
                [
                    'origin_city' => $route['origin'],
                    'destination_city' => $route['destination'],
                ],
                [
                    'price_per_kg' => $route['price_per_kg'],
                    'estimated_days' => $route['estimated_days'],
                ],
            );

            Rate::updateOrCreate(
                [
                    'origin_city' => $route['destination'],
                    'destination_city' => $route['origin'],
                ],
                [
                    'price_per_kg' => $route['price_per_kg'],
                    'estimated_days' => $route['estimated_days'],
                ],
            );
        }

        $defaultPassword = Hash::make('password123');

        User::updateOrCreate([
            'email' => 'admin@ekspedisi.test',
        ], [
            'name' => 'Admin',
            'password' => $defaultPassword,
            'role' => User::ROLE_ADMIN,
            'branch_id' => $branches['jakarta']->id,
        ]);

        User::updateOrCreate([
            'email' => 'manager@ekspedisi.test',
        ], [
            'name' => 'Manager',
            'password' => $defaultPassword,
            'role' => User::ROLE_MANAGER,
            'branch_id' => $branches['jakarta']->id,
        ]);

        User::updateOrCreate([
            'email' => 'cashier@ekspedisi.test',
        ], [
            'name' => 'Cashier',
            'password' => $defaultPassword,
            'role' => User::ROLE_CASHIER,
            'branch_id' => $branches['jakarta']->id,
        ]);

        User::updateOrCreate([
            'email' => 'casier@ekspedisi.test',
        ], [
            'name' => 'Casier',
            'password' => $defaultPassword,
            'role' => User::ROLE_CASIER,
            'branch_id' => $branches['jakarta']->id,
        ]);

        $courierDefinitions = [
            ['name' => 'Courier Pickup Jakarta', 'email' => 'courier.pickup.jkt@ekspedisi.test', 'branch' => 'jakarta'],
            ['name' => 'Courier HTH Jakarta', 'email' => 'courier.hth.jkt@ekspedisi.test', 'branch' => 'jakarta'],
            ['name' => 'Courier Drop Jakarta', 'email' => 'courier.drop.jkt@ekspedisi.test', 'branch' => 'jakarta'],
            ['name' => 'Courier Pickup Bandung', 'email' => 'courier.pickup.bdg@ekspedisi.test', 'branch' => 'bandung'],
            ['name' => 'Courier HTH Bandung', 'email' => 'courier.hth.bdg@ekspedisi.test', 'branch' => 'bandung'],
            ['name' => 'Courier Drop Bandung', 'email' => 'courier.drop.bdg@ekspedisi.test', 'branch' => 'bandung'],
            ['name' => 'Courier Pickup Semarang', 'email' => 'courier.pickup.smg@ekspedisi.test', 'branch' => 'semarang'],
            ['name' => 'Courier HTH Semarang', 'email' => 'courier.hth.smg@ekspedisi.test', 'branch' => 'semarang'],
            ['name' => 'Courier Drop Semarang', 'email' => 'courier.drop.smg@ekspedisi.test', 'branch' => 'semarang'],
            ['name' => 'Courier Pickup Yogyakarta', 'email' => 'courier.pickup.yk@ekspedisi.test', 'branch' => 'yogyakarta'],
            ['name' => 'Courier HTH Yogyakarta', 'email' => 'courier.hth.yk@ekspedisi.test', 'branch' => 'yogyakarta'],
            ['name' => 'Courier Drop Yogyakarta', 'email' => 'courier.drop.yk@ekspedisi.test', 'branch' => 'yogyakarta'],
            ['name' => 'Courier Pickup Surabaya', 'email' => 'courier.pickup.sby@ekspedisi.test', 'branch' => 'surabaya'],
            ['name' => 'Courier HTH Surabaya', 'email' => 'courier.hth.sby@ekspedisi.test', 'branch' => 'surabaya'],
            ['name' => 'Courier Drop Surabaya', 'email' => 'courier.drop.sby@ekspedisi.test', 'branch' => 'surabaya'],
        ];

        $couriers = collect($courierDefinitions)->mapWithKeys(function (array $courier) use ($branches, $defaultPassword) {
            $user = User::updateOrCreate([
                'email' => $courier['email'],
            ], [
                'name' => $courier['name'],
                'password' => $defaultPassword,
                'role' => User::ROLE_COURIER,
                'branch_id' => $branches[$courier['branch']]->id,
            ]);

            return [$courier['email'] => $user];
        });

        Vehicle::updateOrCreate(
            ['plate_number' => 'B 1010 JKT'],
            ['type' => 'motor', 'courier_id' => $couriers['courier.pickup.jkt@ekspedisi.test']->id],
        );
        Vehicle::updateOrCreate(
            ['plate_number' => 'B 2020 JKT'],
            ['type' => 'truck', 'courier_id' => $couriers['courier.hth.jkt@ekspedisi.test']->id],
        );
        Vehicle::updateOrCreate(
            ['plate_number' => 'B 3030 JKT'],
            ['type' => 'mobil', 'courier_id' => $couriers['courier.drop.jkt@ekspedisi.test']->id],
        );
        Vehicle::updateOrCreate(
            ['plate_number' => 'D 1010 BDG'],
            ['type' => 'motor', 'courier_id' => $couriers['courier.pickup.bdg@ekspedisi.test']->id],
        );
        Vehicle::updateOrCreate(
            ['plate_number' => 'D 2020 BDG'],
            ['type' => 'truck', 'courier_id' => $couriers['courier.hth.bdg@ekspedisi.test']->id],
        );
        Vehicle::updateOrCreate(
            ['plate_number' => 'D 3030 BDG'],
            ['type' => 'mobil', 'courier_id' => $couriers['courier.drop.bdg@ekspedisi.test']->id],
        );
        Vehicle::updateOrCreate(
            ['plate_number' => 'H 4040 SMG'],
            ['type' => 'truck', 'courier_id' => $couriers['courier.hth.smg@ekspedisi.test']->id],
        );
        Vehicle::updateOrCreate(
            ['plate_number' => 'AB 5050 YK'],
            ['type' => 'mobil', 'courier_id' => $couriers['courier.drop.yk@ekspedisi.test']->id],
        );
        Vehicle::updateOrCreate(
            ['plate_number' => 'L 6060 SBY'],
            ['type' => 'truck', 'courier_id' => $couriers['courier.hth.sby@ekspedisi.test']->id],
        );

        Customer::updateOrCreate([
            'email' => 'customer@ekspedisi.test',
        ], [
            'name' => 'Customer Demo',
            'password' => $defaultPassword,
            'address' => 'Jl. Kemang Raya No. 10',
            'city' => 'Jakarta',
            'phone' => '081200000000',
        ]);

        Customer::updateOrCreate([
            'email' => 'customer.bdg@ekspedisi.test',
        ], [
            'name' => 'Customer Bandung',
            'password' => $defaultPassword,
            'address' => 'Jl. Dago No. 12',
            'city' => 'Bandung',
            'phone' => '081211111111',
        ]);

        Customer::updateOrCreate([
            'email' => 'customer.sby@ekspedisi.test',
        ], [
            'name' => 'Customer Surabaya',
            'password' => $defaultPassword,
            'address' => 'Jl. Darmo No. 18',
            'city' => 'Surabaya',
            'phone' => '081322222222',
        ]);
    }
}
