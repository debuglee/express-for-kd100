<?php

namespace Debuglee\Express;

use Illuminate\Support\ServiceProvider;

class ExpressServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
         // 注册配置文件 Config
        $this->mergeConfigFrom( __DIR__.'/Config/express.php', 'express');

        // 定义部署的文件
        $this->publishes([
            __DIR__.'/Config/express.php' => config_path('express.php'),
        ], 'config');

        $this->app['express'] = $this->app->share(function($app) {
            return new Express;
        });
    }
}
