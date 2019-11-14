<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\PersonalAccessGrant;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Set utf8mb4 length   设置utf8mb4长度
        Schema::defaultStringLength(191);
        //Register auth routes   注册身份验证路由
        Passport::routes();
        //$this->app->get(AuthorizationServer::class)->enableGrantType(new PersonalAccessGrant(),new \DateInterval('PT60S'));
    }
}
