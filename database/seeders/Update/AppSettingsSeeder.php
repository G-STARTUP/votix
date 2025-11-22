<?php

namespace Database\Seeders\Update;

use Illuminate\Database\Seeder;
use App\Models\Admin\AppSettings;

class AppSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $app_settings = array(
            'version' => '1.6.0',
            'agent_version' => '1.6.0',
            'merchant_version' => '1.6.0',
          );
        $appSettings = AppSettings::first();
        $appSettings->update($app_settings);
    }
}
