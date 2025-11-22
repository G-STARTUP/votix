<?php

namespace Database\Seeders\Update;

use App\Constants\GlobalConst;
use Illuminate\Database\Seeder;
use Exception;
use App\Models\VirtualCardApi;

class VirtualApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        try{
            $virtualApi = VirtualCardApi::first();
            $config_data = (array)$virtualApi->config;
            $stripe_mode = GlobalConst::SANDBOX;
            if (!array_key_exists('stripe_mode', $config_data)) {
                $config_data['stripe_mode'] = $stripe_mode;
            }
            $virtualApi->config = (object)$config_data;
            $virtualApi->save();
        }catch(Exception $e){}

    }
}
