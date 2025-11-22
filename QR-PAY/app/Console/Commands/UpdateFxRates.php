<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateFxRates extends Command
{
    protected $signature = 'currency:update';
    protected $description = 'Fetch and update fiat currency exchange rates';

    public function handle(): int
    {
        $cfg = config('fx');
        $url = $cfg['api_url'];
        $base = $cfg['base'];
        $symbols = implode(',', $cfg['symbols']);

        // exchangerate.host expects symbols comma separated, base optional
        $query = ['base' => $base, 'symbols' => $symbols];
        $this->info('Fetching FX rates for: ' . $symbols);
        try {
            $resp = Http::timeout($cfg['timeout'])->get($url, $query);
            if(!$resp->ok()) {
                $this->error('FX API error status: '.$resp->status());
                return Command::FAILURE;
            }
            $json = $resp->json();
            $rates = $json['rates'] ?? [];
            // Some APIs use upper-case codes; ensure keys match expected symbols list
            if(empty($rates)) {
                // Attempt alternate key casing / nested structures
                foreach(['Data','data','Rates','rates'] as $alt){
                    if(isset($json[$alt]) && is_array($json[$alt]) && !empty($json[$alt])) { $rates = $json[$alt]; break; }
                }
            }
            // If still empty and we have individual symbol fields try gather them
            if(empty($rates)) {
                $rates = [];
                foreach(explode(',', $symbols) as $code){
                    $code = trim($code);
                    if(isset($json[$code]) && is_numeric($json[$code])) { $rates[$code] = $json[$code]; }
                }
            }
            if(empty($rates)) {
                $this->error('No rates returned');
                return Command::FAILURE;
            }
            DB::beginTransaction();
            foreach($rates as $code => $rate) {
                // Only update if rate is numeric and positive
                if(!is_numeric($rate) || $rate <= 0) { continue; }
                $updated = DB::table('currencies')->where('code', $code)->update([
                    'rate' => $rate,
                    'updated_at' => now(),
                ]);
                if($updated) {
                    $this->line("Updated rate for $code => $rate");
                }
            }
            DB::commit();
            $this->info('FX rates update complete');
            return Command::SUCCESS;
        } catch(\Throwable $e) {
            DB::rollBack();
            Log::warning('FX rate update failed', ['error' => $e->getMessage()]);
            $this->error('Exception: '.$e->getMessage());
            return Command::FAILURE;
        }
    }
}