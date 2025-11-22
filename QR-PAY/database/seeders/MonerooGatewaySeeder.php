<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Str;

class MonerooGatewaySeeder extends Seeder
{
    /**
     * Seed Moneroo payment gateway, currency, wallet address and a test crypto transaction.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $now = now();
            // Check existing gateway by alias or name
            $exists = DB::table('payment_gateways')->where('alias', PaymentGatewayConst::MONEROO)->first();
            if ($exists) {
                // If already seeded, ensure credentials array has expected configurable entries
                $currentCreds = json_decode($exists->credentials ?? '[]', true);
                $labels = collect($currentCreds)->pluck('label')->map(fn($l)=>strtolower($l))->toArray();
                $expected = [
                    'api key' => '',
                    'webhook secret' => '',
                    'withdraw api key' => '',
                    'webhook url' => '',
                    'payment link base' => '',
                ];
                $modified = false;
                foreach($expected as $label=>$value) {
                    if(!in_array($label,$labels)) {
                        $currentCreds[] = [
                            'id'    => uniqid(),
                            'label' => ucwords($label),
                            'value' => $value,
                            'type'  => 'text',
                        ];
                        $modified = true;
                    }
                }
                if($modified) {
                    DB::table('payment_gateways')->where('id',$exists->id)->update([
                        'credentials' => json_encode($currentCreds),
                        'updated_at'  => $now,
                    ]);
                }
                return; // Do not re-insert existing records
            }

            // $now already defined above
            $supported = json_encode(['XMR']);
            $credentialsArr = [
                [ 'id'=> uniqid(), 'label' => 'API Key',           'value' => 'moneroo_test_api_key', 'type' => 'text' ],
                [ 'id'=> uniqid(), 'label' => 'Webhook Secret',    'value' => 'moneroo_test_secret', 'type' => 'text' ],
                [ 'id'=> uniqid(), 'label' => 'Withdraw API Key',  'value' => 'moneroo_withdraw_key', 'type' => 'text' ],
                [ 'id'=> uniqid(), 'label' => 'Webhook URL',       'value' => 'http://127.0.0.1:8000/api/moneroo/webhook', 'type' => 'text' ],
                [ 'id'=> uniqid(), 'label' => 'Payment Link Base', 'value' => 'http://127.0.0.1:8000/moneroo/payment', 'type' => 'text' ],
            ];
            $credentials = json_encode($credentialsArr); // configurable via admin UI

            // Ensure an admin exists for last_edit_by (fallback create if none)
            $admin = DB::table('admins')->first();
            if(!$admin) {
                $adminId = DB::table('admins')->insertGetId([
                    'firstname' => 'Seeder',
                    'lastname' => 'Admin',
                    'username' => 'seeder_admin',
                    'user_type' => 'ADMIN',
                    'email' => 'seedadmin@example.com',
                    'password' => bcrypt('password'),
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else { $adminId = $admin->id; }

            $gatewayId = DB::table('payment_gateways')->insertGetId([
                'name'                  => 'Moneroo',
                'title'                 => 'Moneroo',
                'slug'                  => Str::slug(PaymentGatewayConst::ADDMONEY),
                'code'                  => 0, // 0 or next incremental code logic if required
                'alias'                 => PaymentGatewayConst::MONEROO,
                'type'                  => PaymentGatewayConst::AUTOMATIC,
                'crypto'                => 1,
                'supported_currencies'  => $supported,
                'credentials'           => $credentials,
                'status'                => PaymentGatewayConst::ACTIVE,
                'env'                   => env('MONEROO_ENV','SANDBOX'),
                'last_edit_by'          => $adminId,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            // Currency record (XMR) for add money limits/charges
            $currencyAlias = 'moneroo-xmr';
            $currencyId = DB::table('payment_gateway_currencies')->insertGetId([
                'payment_gateway_id'    => $gatewayId,
                'name'                  => 'Monero',
                'alias'                 => $currencyAlias,
                'currency_code'         => 'XMR',
                'currency_symbol'       => 'XMR',
                'image'                 => null,
                'min_limit'             => 0.01,
                'max_limit'             => 10,
                'percent_charge'        => 0,
                'fixed_charge'          => 0,
                'rate'                  => 1,
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            // Wallet (crypto asset) with active address
            $walletAddress = '48A1ExampleMoneroAddressXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
            $credentialsObj = [
                'credentials' => [
                    [
                        'id'        => uniqid().time(),
                        'address'   => $walletAddress,
                        'status'    => true,
                        'balance'   => [
                            'balance' => 0,
                        ],
                    ]
                ]
            ];
            $cryptoAssetId = DB::table('crypto_assets')->insertGetId([
                'payment_gateway_id'   => $gatewayId,
                'type'                 => PaymentGatewayConst::ASSET_TYPE_WALLET,
                'chain'                => 'XMR',
                'coin'                 => 'XMR',
                'credentials'          => json_encode($credentialsObj),
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);

            // Test crypto transaction (NOT_USED) to allow confirmation flow
            DB::table('crypto_transactions')->insert([
                'internal_trx_type'    => PaymentGatewayConst::TYPEADDMONEY,
                'internal_trx_ref_id'  => null,
                'transaction_type'     => 'Native',
                'sender_address'       => 'SenderPlaceholder',
                'receiver_address'     => $walletAddress,
                'amount'               => '5',
                'asset'                => 'XMR',
                'block_number'         => null,
                'txn_hash'             => 'TEST_MONEROO_HASH_'.uniqid(),
                'chain'                => 'XMR',
                'callback_response'    => null,
                'status'               => PaymentGatewayConst::NOT_USED,
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        });
    }
}
