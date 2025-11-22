<?php

namespace App\Traits\PaymentGateway;

use Exception;
use Illuminate\Support\Str;
use App\Constants\PaymentGatewayConst;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;

trait Moneroo {

    private $moneroo_gateway_credentials;
    private $request_credentials;
    private function monerooGetCredential($gateway, array $keys, $fallback = null) {
        if(!$gateway) return $fallback;
        $val = \App\Http\Helpers\PaymentGateway::getValueFromGatewayCredentials($gateway, $keys);
        return $val !== '' ? $val : $fallback;
    }

    public function monerooInit($output = null) {
        if(!$output) $output = $this->output;

        $gateway = $output['gateway'];
        $currency = $output['currency'];

        // Moneroo assumed crypto native address allocation (external or pre-created)
        $crypto_asset = $gateway->cryptoAssets->where('coin', $currency->currency_code)->first();
        $active_wallet = collect($crypto_asset->credentials->credentials ?? [])->where('status', true)->first();
        if(!$crypto_asset || !$active_wallet) throw new Exception(__('Gateway is not available right now! Please contact with system administration'));

        if($output['type'] == PaymentGatewayConst::TYPEADDMONEY) {
            // Custom payment link base if configured
            $paymentLinkBase = $this->monerooGetCredential($gateway, ['Payment Link Base']);
            try {
                if(userGuard()['guard'] == 'agent') {
                    $trx_id = $this->createMonerooAddMoneyTransactionAgent($output, $active_wallet);
                    $redirect_route = 'agent.add.money.payment.crypto.address';
                } elseif(auth()->guard(get_auth_guard())->check()) {
                    $trx_id = $this->createMonerooAddMoneyTransaction($output, $active_wallet);
                    $redirect_route = 'user.add.money.payment.crypto.address';
                }
            } catch(Exception $e) {
                throw new Exception(__('Something went wrong! Please try again.'));
            }
            if($paymentLinkBase) {
                return redirect(rtrim($paymentLinkBase,'/').'/'.$trx_id);
            }
            return redirect()->route($redirect_route, $trx_id);
        }

        throw new Exception(__('No Action Executed!'));
    }

    public function monerooInitApi($output = null) {
        if(!$output) $output = $this->output;

        $gateway = $output['gateway'];
        $currency = $output['currency'];
        $crypto_asset = $gateway->cryptoAssets->where('coin', $currency->currency_code)->first();
        $active_wallet = collect($crypto_asset->credentials->credentials ?? [])->where('status', true)->first();

        if(!$crypto_asset || !$active_wallet) throw new Exception(__('Gateway is not available right now! Please contact with system administration'));

        if($output['type'] == PaymentGatewayConst::TYPEADDMONEY) {
            $paymentLinkBase = $this->monerooGetCredential($gateway, ['Payment Link Base']);
            $webhookUrl      = $this->monerooGetCredential($gateway, ['Webhook URL'], route('api.add.money.moneroo.webhook'));
            try {
                if(authGuardApi()['guard'] == 'agent_api') {
                    $trx_id = $this->createMonerooAddMoneyTransactionAgent($output, $active_wallet);
                    $submit_url = route('api.agent.add.money.payment.crypto.confirm', $trx_id);
                } elseif(auth()->guard(get_auth_guard())->check()) {
                    $trx_id = $this->createMonerooAddMoneyTransaction($output, $active_wallet);
                    $submit_url = route('api.user.add.money.payment.crypto.confirm', $trx_id);
                }
            } catch(Exception $e) {
                throw new Exception(__('Something went wrong! Please try again.'));
            }
            if(request()->expectsJson()) {
                return [
                    'trx' => $trx_id,
                    'gateway_type' => $output['gateway']->type,
                    'gateway_currency_name' => $output['currency']->name,
                    'alias' => $output['currency']->alias,
                    'identify' => $output['gateway']->name,
                    'redirect_url' => false,
                    'redirect_links' => [],
                    'type' => PaymentGatewayConst::CRYPTO_NATIVE,
                    'address_info' => [
                        'coin' => $crypto_asset->coin,
                        'address' => $active_wallet->address,
                        'input_fields' => $this->monerooUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY),
                        'submit_url' => $submit_url,
                        'webhook_url' => $webhookUrl,
                    ],
                    'payment_link_base' => $paymentLinkBase,
                ];
            }
            throw new Exception(__('Something went wrong! Please try again.'));
        }
        throw new Exception(__('No Action Executed!'));
    }

    public function createMonerooAddMoneyTransaction($output, $active_wallet) {
        $user = auth()->guard(get_auth_guard())->user();
        DB::beginTransaction();
        try {
            $trx_id = 'AM'.getTrxNum();
            $qr_image = 'https://qrcode.tec-it.com/API/QRCode?data='.$active_wallet->address;

            $inserted_id = DB::table('transactions')->insertGetId([
                'user_id' => $user->id,
                'user_wallet_id' => $output['wallet']->id,
                'payment_gateway_currency_id' => $output['currency']->id,
                'type' => $output['type'],
                'trx_id' => $trx_id,
                'request_amount' => $output['amount']->requested_amount,
                'payable' => $output['amount']->total_amount,
                'available_balance' => $output['wallet']->balance,
                'remark' => ucwords(remove_speacial_char($output['type'])).' With '.$output['gateway']->name,
                'details' => json_encode([
                    'charge' => $output['amount'],
                    'payment_info' => [
                        'payment_type' => PaymentGatewayConst::CRYPTO,
                        'currency' => $output['currency']->currency_code,
                        'receiver_address' => $active_wallet->address,
                        'receiver_qr_image' => $qr_image,
                        'requirements' => $this->monerooUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY),
                    ],
                    'data' => json_encode($output),
                ]),
                'status' => PaymentGatewayConst::STATUSWAITING,
                'attribute' => PaymentGatewayConst::RECEIVED,
                'created_at' => now(),
            ]);
            $this->insertMonerooCharges($output, $inserted_id);
            $this->insertMonerooDevice($output, $inserted_id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__('Something went wrong! Please try again.'));
        }
        return $trx_id;
    }

    public function createMonerooAddMoneyTransactionAgent($output, $active_wallet) {
        DB::beginTransaction();
        try {
            $trx_id = 'AM'.getTrxNum();
            $qr_image = 'https://qrcode.tec-it.com/API/QRCode?data='.$active_wallet->address;
            $inserted_id = DB::table('transactions')->insertGetId([
                'agent_id' => authGuardApi()['user']->id,
                'agent_wallet_id' => $output['wallet']->id,
                'payment_gateway_currency_id' => $output['currency']->id,
                'type' => $output['type'],
                'trx_id' => $trx_id,
                'request_amount' => $output['amount']->requested_amount,
                'payable' => $output['amount']->total_amount,
                'available_balance' => $output['wallet']->balance,
                'remark' => ucwords(remove_speacial_char($output['type'])).' With '.$output['gateway']->name,
                'details' => json_encode([
                    'charge' => $output['amount'],
                    'amount' => $output['amount'],
                    'payment_info' => [
                        'payment_type' => PaymentGatewayConst::CRYPTO,
                        'currency' => $output['currency']->currency_code,
                        'receiver_address' => $active_wallet->address,
                        'receiver_qr_image' => $qr_image,
                        'requirements' => $this->monerooUserTransactionRequirements(PaymentGatewayConst::TYPEADDMONEY),
                    ],
                    'data' => json_encode($output),
                ]),
                'status' => PaymentGatewayConst::STATUSWAITING,
                'attribute' => PaymentGatewayConst::RECEIVED,
                'created_at' => now(),
            ]);
            $this->insertMonerooCharges($output, $inserted_id);
            $this->insertMonerooDevice($output, $inserted_id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__('Something went wrong! Please try again.'));
        }
        return $trx_id;
    }

    public function insertMonerooCharges($output, $id) {
        DB::beginTransaction();
        try {
            DB::table('transaction_charges')->insert([
                'transaction_id' => $id,
                'percent_charge' => $output['amount']->percent_charge,
                'fixed_charge' => $output['amount']->fixed_charge,
                'total_charge' => $output['amount']->total_charge,
                'created_at' => now(),
            ]);
            DB::commit();
        } catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__('Something went wrong! Please try again.'));
        }
    }

    public function insertMonerooDevice($output, $id) {
        $client_ip = request()->ip() ?? false;
        $location = geoip()->getLocation($client_ip);
        $agent = new Agent();
        $mac = '';
        DB::beginTransaction();
        try {
            DB::table('transaction_devices')->insert([
                'transaction_id' => $id,
                'ip' => $client_ip,
                'mac' => $mac,
                'city' => $location['city'] ?? '',
                'country' => $location['country'] ?? '',
                'longitude' => $location['lon'] ?? '',
                'latitude' => $location['lat'] ?? '',
                'timezone' => $location['timezone'] ?? '',
                'browser' => $agent->browser() ?? '',
                'os' => $agent->platform() ?? '',
            ]);
            DB::commit();
        } catch(Exception $e) {
            DB::rollBack();
            throw new Exception(__('Something went wrong! Please try again.'));
        }
    }

    public function monerooUserTransactionRequirements($trx_type = null) {
        $requirements = [
            PaymentGatewayConst::TYPEADDMONEY => [
                [
                    'type' => 'text',
                    'label' => 'Txn Hash',
                    'placeholder' => 'Enter Txn Hash',
                    'name' => 'txn_hash',
                    'required' => true,
                    'validation' => [
                        'min' => '0',
                        'max' => '250',
                        'required' => true,
                        // Monero transaction hashes: 64 hex characters
                        'regex' => '^[A-Fa-f0-9]{64}$',
                    ],
                ],
            ],
        ];
        if($trx_type) {
            if(!array_key_exists($trx_type, $requirements)) throw new Exception(__('User Transaction Requirements Not Found!'));
            return $requirements[$trx_type];
        }
        return $requirements;
    }

    public function isMoneroo($gateway) {
        $search_keyword = ['moneroo','moneroo gateway','gateway moneroo','crypto moneroo'];
        $gateway_name = $gateway->name;
        $search_text = Str::lower($gateway_name);
        $search_text = preg_replace('/[^A-Za-z0-9]/','', $search_text);
        foreach($search_keyword as $keyword) {
            $keyword = Str::lower($keyword);
            $keyword = preg_replace('/[^A-Za-z0-9]/','', $keyword);
            if($keyword == $search_text) {
                return true;
            }
        }
        return false;
    }
}
