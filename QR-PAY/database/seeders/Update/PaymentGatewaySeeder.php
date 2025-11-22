<?php

namespace Database\Seeders\Update;

use Illuminate\Database\Seeder;
use App\Models\Admin\PaymentGateway;
use App\Models\Admin\PaymentGatewayCurrency;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        //Bkash for add money
        $bkash = PaymentGateway::orderBy('id',"DESC")->first();
        if(!PaymentGateway::where('alias','bkash')->exists()){
            $payment_gateways_id = $bkash->id+1;
            $payment_gateways_code = PaymentGateway::max('code')+5;
            $payment_gateways = array(
                array('id' => $payment_gateways_id,'slug' => 'add-money','code' => $payment_gateways_code,'type' => 'AUTOMATIC','name' => 'bKash','title' => 'bKash Payment  Gateway','alias' => 'bkash','image' => 'seeder/bkash.webp','credentials' => '[{"label":"App Key","placeholder":"Enter App Key","name":"app-key","value":"4f6o0cjiki2rfm34kfdadl1eqq"},{"label":"Secret Key","placeholder":"Enter Secret Key","name":"secret-key","value":"2is7hdktrekvrbljjh44ll3d9l1dtjo4pasmjvs5vl5qr3fug4b"},{"label":"Username","placeholder":"Enter Username","name":"username","value":"sandboxTokenizedUser02"},{"label":"Password","placeholder":"Enter Password","name":"password","value":"sandboxTokenizedUser02@12345"},{"label":"Sandbox Url","placeholder":"Enter Sandbox Url","name":"sandbox-url","value":"https:\\/\\/tokenized.sandbox.bka.sh\\/v1.2.0-beta"},{"label":"Production Url","placeholder":"Enter Production Url","name":"production-url","value":"https:\\/\\/tokenized.pay.bka.sh\\/v1.2.0-beta"}]','supported_currencies' => '["BDT"]','crypto' => '0','desc' => NULL,'input_fields' => NULL,'status' => '1','last_edit_by' => '1','created_at' => now(),'updated_at' => now(),'env' => 'SANDBOX')
            );
            PaymentGateway::insert($payment_gateways);

            $payment_gateway_currencies = array(
                array('payment_gateway_id' => $payment_gateways_id,'name' => 'bKash BDT','alias' => 'bkash-bdt-automatic','currency_code' => 'BDT','currency_symbol' => '৳','image' => 'seeder/bkash.webp','min_limit' => '100.00000000','max_limit' => '1000.00000000','percent_charge' => '1.00000000','fixed_charge' => '1.00000000','rate' => '117.54000000','created_at' => now(),'updated_at' => now())
              );
            PaymentGatewayCurrency::insert($payment_gateway_currencies);
        }

        //authorize for add money
        $authorize = PaymentGateway::orderBy('id',"DESC")->first();
        if(!PaymentGateway::where('alias','authorize')->exists()){
            $payment_gateways_id = $authorize->id+1;
            $payment_gateways_code = PaymentGateway::max('code')+5;
            $payment_gateways = array(

                array('id' => $payment_gateways_id ,'slug' => 'add-money','code' =>  $payment_gateways_code,'type' => 'AUTOMATIC','name' => 'Authorize','title' => 'Authorize Payment Gateway','alias' => 'authorize','image' => 'seeder/authorize.net.png','credentials' => '[{"label":"App Login ID","placeholder":"Enter App Login ID","name":"app-login-id","value":"5wEnX48K4W85"},{"label":"Transaction Key","placeholder":"Enter Transaction Key","name":"transaction-key","value":"8Gf4uq9Nb55y7Yzg"},{"label":"Signature Key","placeholder":"Enter Signature Key","name":"signature-key","value":"B17BB1A22564FD12838A05884B51BE09EA7A98AD61E81BF6BD8BBD69414CE047D30FCBACE679AD76807670D3D261F920D80527332879790B7B4432642EA390D6"}]','supported_currencies' => '["USD"]','crypto' => '0','desc' => NULL,'input_fields' => NULL,'status' => '1','last_edit_by' => '1','created_at' => now(),'updated_at' => now(),'env' => NULL)
            );

            PaymentGateway::insert($payment_gateways);

            $payment_gateway_currencies = array(
                array('payment_gateway_id' => $payment_gateways_id,'name' => 'Authorize USD','alias' => 'authorize-usd-automatic','currency_code' => 'USD','currency_symbol' => '$','image' => 'seeder/authorize.net.png','min_limit' => '1.00000000','max_limit' => '1000.00000000','percent_charge' => '1.00000000','fixed_charge' => '1.00000000','rate' => '1.00000000','created_at' => now(),'updated_at' => now())
            );
            PaymentGatewayCurrency::insert($payment_gateway_currencies);
        }

    }
}
