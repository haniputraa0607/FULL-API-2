<?php

namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Route;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();
        
        Route::group(['middleware' => ['custom_auth', 'decrypt_pin:password,username']], function () {
            Passport::tokensCan([
                'be' => 'Manage admin panel scope',
                'apps' => 'Manage mobile scope',
                'web-apps' => 'Manage web apps scope',
                'franchise-client' => 'General scope franchise',
                'franchise-super-admin' => 'Manage super admin franchise scope',
                'franchise-user' => 'Manage admin franchise scope',
                'landing-page' => 'Manage new partner franchise scope',
                'partners'=>'Manage partner',
                'mitra-apps' => 'Manage mitra mobile app scope',
                'outlet-display' => 'Manage Outlet Display',
                'client' => 'Manage client scope',
                'employee-apps' => 'Manage employee scope',
                'employees' => 'Manage customer scope',
            ]);
            Passport::routes(function ($router) {
                return $router->forAccessTokens();
            });
        });

        Passport::tokensExpireIn(now()->addDays(15000));
    }
}
