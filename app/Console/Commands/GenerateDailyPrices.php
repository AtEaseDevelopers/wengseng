<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ProductDailyPrice;
use Carbon\Carbon;

class GenerateDailyPrices extends Command
{
    protected $signature = 'prices:generate-daily';
    protected $description = 'Generate tomorrow\'s daily prices by copying today\'s prices';

    public function handle()
    {
        $today = Carbon::today()->format('Y-m-d');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        // Get all active prices for today
        $todayPrices = ProductDailyPrice::where('date', $today)
            ->where('status', 'active')
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($todayPrices as $price) {
            // Check if tomorrow's price already exists
            $exists = ProductDailyPrice::where('date', $tomorrow)
                ->where('product_id', $price->product_id)
                ->where('user_category', $price->user_category)
                ->where('status', 'active')
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            // Create tomorrow's price
            ProductDailyPrice::create([
                'date' => $tomorrow,
                'product_id' => $price->product_id,
                'user_category' => $price->user_category,
                'price' => $price->price,
                'status' => 'active',
            ]);
            $created++;
        }

        $this->info("Daily prices generated: {$created} created, {$skipped} skipped (already exist).");
    }
}
