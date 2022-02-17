<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Lib\MyHelper;
use Illuminate\Support\Facades\Config;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        $date_end= MyHelper::setting('hs_income_delivery_cut_off_end_date', 'value', 25);
        $date_middle= MyHelper::setting('hs_income_delivery_cut_off_middle_date', 'value', 11);
        Config::set([
            'income_date_end' => $date_end,
            'income_date_middle' =>$date_middle
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(\Laravel\Passport\Http\Controllers\AccessTokenController::class, \App\Http\Controllers\AccessTokenController::class);
		$this->app->bind('mailgun.client', function() {
			return \Http\Adapter\Guzzle6\Client::createWithConfig([
			
			]);
		});
        if ($this->app->environment() == 'local') {
            $this->app->register(\Reliese\Coders\CodersServiceProvider::class);
        }
    }
}
