<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use App\Http\Helpers\Api\Helpers;
use App\Constants\PaymentGatewayConst;
use App\Models\Transaction;
use App\Models\Admin\CryptoTransaction;
use App\Models\Admin\PaymentGateway;
use App\Http\Helpers\PaymentGateway as GatewayHelper;
use Illuminate\Support\Facades\DB;
use Exception;

class MonerooWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Prefer credential stored secret over config fallback
        $gateway = PaymentGateway::active()->gateway(PaymentGatewayConst::MONEROO)->first();
        $secretFromCreds = $gateway ? GatewayHelper::getValueFromGatewayCredentials($gateway, ['Webhook Secret','Webhook Key']) : '';
        // Grace secret support for rotation overlap
        $graceSecret = $gateway ? GatewayHelper::getValueFromGatewayCredentials($gateway, ['Webhook Secret Grace']) : '';
        $graceExpiry = $gateway ? GatewayHelper::getValueFromGatewayCredentials($gateway, ['Webhook Secret Grace Expires At']) : '';
        $secret    = $secretFromCreds ?: config('moneroo.webhook_secret');
        $signature = $request->header('X-Moneroo-Signature');
        $payload   = $request->getContent();
        if($signature) {
            $valid = false;
            $candidates = [];
            if($secret) { $candidates[] = $secret; }
            if($graceSecret && $graceExpiry) {
                try { if(now()->lt(now()->parse($graceExpiry))) { $candidates[] = $graceSecret; } } catch(Exception $e) { /* ignore parse errors */ }
            }
            foreach($candidates as $sec) {
                $expected = hash_hmac('sha256', $payload, $sec);
                if(hash_equals($expected, $signature)) { $valid = true; break; }
            }
            if(!$valid) { return Helpers::error(["Invalid signature"], 401); }
        }

        // Gracefully parse JSON if content-type missing (simulation scripts)
        $decoded = [];
        if(empty($request->all())) {
            $raw = $payload;
            $tmp = json_decode($raw, true);
            if(is_array($tmp)) { $decoded = $tmp; }
        }
        $event      = $request->input('event')            ?? ($decoded['event'] ?? null);           // e.g. 'deposit'
        $txnHash    = $request->input('txn_hash')         ?? ($decoded['txn_hash'] ?? null);        // blockchain transaction hash
        $address    = $request->input('receiver_address') ?? ($decoded['receiver_address'] ?? null);
        $asset      = $request->input('asset')            ?? ($decoded['asset'] ?? null);           // e.g. 'XMR'
        $amount     = $request->input('amount')           ?? ($decoded['amount'] ?? null);          // numeric string

        Log::info('Moneroo webhook received', [
            'event' => $event,
            'txn_hash' => $txnHash,
            'receiver_address' => $address,
            'asset' => $asset,
            'amount' => $amount,
        ]);

        if($event === 'deposit' && $txnHash && $address && $asset && $amount) {
            // Basic numeric validation & sanitize
            if(!is_numeric($amount) || $amount <= 0) {
                return Helpers::success(['status' => 'ok']);
            }
            // Direct lookup using dedicated receiver_address column
            // Only consider transactions whose associated gateway currency is currently enabled
            $transaction = Transaction::where('status', PaymentGatewayConst::STATUSWAITING)
                ->where('receiver_address', $address)
                ->whereHas('gateway_currency', function($q) use ($asset){
                    $q->where('currency_code', $asset)->where('status', 1);
                })
                ->latest()->first();

            if($transaction) {
                // Enforce min/max limits from gateway currency
                $min = $transaction->gateway_currency->min_limit ?? 0;
                $max = $transaction->gateway_currency->max_limit ?? PHP_FLOAT_MAX;
                if($amount < $min || $amount > $max) {
                    return Helpers::success(['status' => 'ok']);
                }
                DB::beginTransaction();
                try {
                    // Upsert crypto transaction record
                    $crypto = CryptoTransaction::where('txn_hash', $txnHash)->first();
                    if(!$crypto) {
                        $crypto = CryptoTransaction::create([
                            'internal_trx_type'   => PaymentGatewayConst::TYPEADDMONEY,
                            'internal_trx_ref_id' => $transaction->id,
                            'transaction_type'    => 'Native',
                            'sender_address'      => $request->input('sender_address'),
                            'receiver_address'    => $address,
                            'amount'              => $amount,
                            'asset'               => $asset,
                            'txn_hash'            => $txnHash,
                            'chain'               => $asset,
                            'status'              => PaymentGatewayConst::USED,
                        ]);
                    } else {
                        $crypto->update(['status' => PaymentGatewayConst::USED]);
                    }

                    // Credit wallet balance (user/agent/merchant)
                    $walletModel = $transaction->user_wallet ?? $transaction->agent_wallet ?? $transaction->merchant_wallet;
                    if($walletModel) {
                        DB::table($walletModel->getTable())
                            ->where('id', $walletModel->id)
                            ->increment('balance', $transaction->request_amount);
                    }

                    // Update transaction details & status via Eloquent
                    $details = json_decode(json_encode($transaction->details), true);
                    $details['payment_info']['txn_hash'] = $txnHash;
                    $transaction->details = json_encode($details);
                    $transaction->status = PaymentGatewayConst::STATUSSUCCESS;
                    $transaction->available_balance = $transaction->available_balance + $transaction->request_amount;
                    $transaction->save();

                    DB::commit();
                    return Helpers::success(['status' => 'confirmed','trx' => $transaction->trx_id,'txn_hash' => $txnHash]);
                } catch(Exception $e) {
                    DB::rollBack();
                    Log::error('Moneroo webhook processing failed', ['error' => $e->getMessage()]);
                    return Helpers::error([$e->getMessage()], 500);
                }
            }
        }

        return Helpers::success(['status' => 'ok']);
    }
}
