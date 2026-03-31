<?php

use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('products:backfill-offer-dates', function () {
    $start = Carbon::yesterday()->startOfDay()->format('Y-m-d H:i:s');
    $end   = Carbon::now()->addYear()->format('Y-m-d H:i:s');
    $count = Product::query()
        ->where('discount', '>', 0)
        ->whereNull('offer_start_date')
        ->whereNull('offer_end_date')
        ->update([
            'offer_start_date' => $start,
            'offer_end_date'   => $end,
        ]);
    $this->info("Set offer window on {$count} product(s) (discount > 0, dates were empty).");
})->purpose('Backfill offer_start_date / offer_end_date for discounted products missing them');
