<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\Response;
use App\Models\Admin\PaymentGatewayCurrency;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentGatewayCurrencyController extends Controller
{
    public function paymentGatewayCurrencyRemove(Request $request) {
        $validator = Validator::make($request->all(),[
            'data_target'       => 'required|numeric',
        ]);

        if($validator->stopOnFirstFailure()->fails()) {
            return Response::error($validator->errors());
        }

        $validated = $validator->validate();

        // find terget Item
        $gateway_currency = PaymentGatewayCurrency::find($validated['data_target']);
        if(!$gateway_currency) {
            $error = ['error' => [__("Payment gateway currency not found!")]];
            return Response::error($error,null,404);
        }

        try{
            if($gateway_currency->image != null) {
                $image_link     = get_files_path('payment-gateways') . "/" . $gateway_currency->image;
                delete_file($image_link);
            }
            $gateway_currency->delete();
        }catch(Exception $e) {
            $error = ['error' => [__("Something went wrong! Please try again.")]];
            return Response::error($error,null,500);
        }

        $success = ['success' => [__("Payment gateway currency deleted successfully!")]];
        return Response::success($success);

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Toggle active status of a payment gateway currency (enable/disable).
     */
    public function toggleStatus(Request $request, $id)
    {
        $currency = PaymentGatewayCurrency::find($id);
        if(!$currency) {
            return Response::error(['error' => [__('Currency not found')]], null, 404);
        }
        try {
            $currency->status = !$currency->status;
            $currency->save();
        } catch(Exception $e) {
            return Response::error(['error' => [__('Update failed')]], null, 500);
        }
        return Response::success(['success' => [__('Status updated')], 'data' => ['id' => $currency->id, 'status' => (bool)$currency->status]]);
    }
    /**
     * Update Moneroo gateway credentials (API Key, Webhook Secret, Withdraw API Key, Webhook URL, Payment Link Base).
     */
    public function updateMonerooCredentials(Request $request)
    {
        $rules = [
            'api_key' => 'nullable|string|max:255',
            'webhook_secret' => 'nullable|string|max:255',
            'withdraw_api_key' => 'nullable|string|max:255',
            'webhook_url' => 'nullable|url|max:255',
            'payment_link_base' => 'nullable|url|max:255',
        ];
        $validator = Validator::make($request->all(), $rules);
        if($validator->stopOnFirstFailure()->fails()) {
            return Response::error($validator->errors());
        }
        $gateway = DB::table('payment_gateways')->where('alias','moneroo')->first();
        if(!$gateway) {
            return Response::error(['error' => [__('Moneroo gateway not found')]], null, 404);
        }
        $creds = json_decode($gateway->credentials ?? '[]', true);
        $map = [
            'API Key' => $request->api_key,
            'Webhook Secret' => $request->webhook_secret,
            'Withdraw API Key' => $request->withdraw_api_key,
            'Webhook URL' => $request->webhook_url,
            'Payment Link Base' => $request->payment_link_base,
        ];
        $changed = false;
        foreach($creds as &$c) {
            if(array_key_exists($c['label'], $map) && $map[$c['label']] !== null) {
                $c['value'] = $map[$c['label']];
                $changed = true;
            }
        }
        if($changed) {
            DB::table('payment_gateways')->where('id',$gateway->id)->update([
                'credentials' => json_encode($creds),
                'updated_at' => now(),
            ]);
        }
        return Response::success(['success' => [__('Credentials updated')], 'changed' => $changed]);
    }

    /**
     * Rotate Moneroo webhook secret creating a grace period for old secret.
     * Grace period defaults to 30 minutes.
     */
    public function rotateMonerooWebhookSecret(Request $request)
    {
        $minutes = (int)($request->input('grace_minutes', 30));
        if($minutes < 0) { $minutes = 0; }
        $gateway = DB::table('payment_gateways')->where('alias','moneroo')->first();
        if(!$gateway) {
            return Response::error(['error' => [__('Moneroo gateway not found')]], null, 404);
        }
        $creds = json_decode($gateway->credentials ?? '[]', true);
        $currentSecret = null;
        foreach($creds as $c){ if($c['label']==='Webhook Secret'){ $currentSecret=$c['value'] ?? null; }}
        $graceExpiry = now()->addMinutes($minutes)->toIso8601String();
        $newSecret = bin2hex(random_bytes(24));
        // Update or insert grace secret + expiry + new secret
        $foundGrace = false; $foundGraceExpiry=false; $foundSecret=false;
        foreach($creds as &$c){
            if($c['label']==='Webhook Secret Grace'){ $c['value']=$currentSecret; $foundGrace=true; }
            if($c['label']==='Webhook Secret Grace Expires At'){ $c['value']=$graceExpiry; $foundGraceExpiry=true; }
            if($c['label']==='Webhook Secret'){ $c['value']=$newSecret; $foundSecret=true; }
        }
        if(!$foundSecret){ $creds[] = ['id'=>uniqid(),'label'=>'Webhook Secret','value'=>$newSecret,'type'=>'text']; }
        if(!$foundGrace){ $creds[] = ['id'=>uniqid(),'label'=>'Webhook Secret Grace','value'=>$currentSecret,'type'=>'text']; }
        if(!$foundGraceExpiry){ $creds[] = ['id'=>uniqid(),'label'=>'Webhook Secret Grace Expires At','value'=>$graceExpiry,'type'=>'text']; }
        DB::table('payment_gateways')->where('id',$gateway->id)->update([
            'credentials'=>json_encode($creds),
            'updated_at'=>now(),
        ]);
        return Response::success(['success'=>[__('Webhook secret rotated')],'data'=>[
            'new_secret'=>$newSecret,
            'grace_secret'=>$currentSecret,
            'grace_expires_at'=>$graceExpiry
        ]]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
