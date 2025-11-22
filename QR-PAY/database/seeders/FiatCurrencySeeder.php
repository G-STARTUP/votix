<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FiatCurrencySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $admin = DB::table('admins')->first();
            if(!$admin) {
                $adminId = DB::table('admins')->insertGetId([
                    'firstname' => 'Seeder',
                    'lastname' => 'Admin',
                    'username' => 'seed_admin_fiat',
                    'user_type' => 'ADMIN',
                    'email' => 'seedadminfiat@example.com',
                    'password' => bcrypt('password'),
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else { $adminId = $admin->id; }

            $fiats = [
                [ 'country' => 'United States',          'name' => 'US Dollar',              'code' => 'USD', 'symbol' => '$',   'rate' => 1,        'default' => 1 ],
                [ 'country' => 'West African States',    'name' => 'West African CFA Franc', 'code' => 'XOF', 'symbol' => 'CFA', 'rate' => 0.0016,   'default' => 0 ],
                [ 'country' => 'Central African States', 'name' => 'Central African CFA Franc','code'=>'XAF','symbol'=>'FCFA','rate'=>0.0016,   'default' => 0 ],
                [ 'country' => 'Nigeria',                'name' => 'Nigerian Naira',         'code' => 'NGN', 'symbol' => '₦',   'rate' => 0.00074,  'default' => 0 ],
            ];

            foreach($fiats as $fiat) {
                $exists = DB::table('currencies')->where('code',$fiat['code'])->first();
                if($exists) continue;
                DB::table('currencies')->insert([
                    'admin_id'    => $adminId,
                    'country'     => $fiat['country'],
                    'name'        => $fiat['name'],
                    'code'        => $fiat['code'],
                    'symbol'      => $fiat['symbol'],
                    'type'        => 'FIAT',
                    'flag'        => null,
                    'rate'        => $fiat['rate'],
                    'sender'      => 1,
                    'receiver'    => 1,
                    'default'     => $fiat['default'],
                    'status'      => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        });
    }
}
