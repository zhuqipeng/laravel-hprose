<?php

namespace Zhuqipeng\LaravelHprose;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use LaravelHproseMethodManage;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadRoute();
        $this->loadCommands();
    }

    /**
     * 加载命令
     *
     * @return void
     */
    protected function loadCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\HproseSocket::class,
            ]);
        }
    }

    /**
     * 加载路由文件
     *
     * @return void
     */
    protected function loadRoute()
    {
        if (str_is('5.2.*', app()::VERSION)) {
            $routeFilePath = base_path('app/Http/rpc.php');
        } else {
            $routeFilePath = base_path('routes/rpc.php');
        }

        if (file_exists($routeFilePath)) {
            require $routeFilePath;
        } else {
            if (config('hprose.demo')) {
                require __DIR__ . '/route.php';
            }
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->setupConfig();
        $this->setupRoute();
        $this->registerHproseSocketServer();
        $this->registerHproseMethodManage();
    }

    /**
     * 设置配置文件
     *
     * @return void
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__ . '/config.php');

        $this->publishes([$source => config_path('hprose.php')]);

        $this->mergeConfigFrom($source, 'hprose');
    }

    /**
     * 设置路由
     *
     * @return void
     */
    protected function setupRoute()
    {
        $source = realpath(__DIR__ . '/route.php');

        if (str_is('5.2.*', app()::VERSION)) {
            $targetPath = base_path('app/Http/rpc.php');
        } else {
            $targetPath = base_path('routes/rpc.php');
        }

        $this->publishes([$source => $targetPath]);
    }

    /**
     * 注册HproseSocketServer
     *
     * @return void
     */
    private function registerHproseSocketServer()
    {
        $this->app->singleton('hprose.socket_server', function ($app) {
            $server = new \Zhuqipeng\LaravelHprose\HproseSocketServer();

            $uris = config('hprose.uris');

            if (!is_array($uris)) {
                throw new \Exception("配置监听地址格式有误", 500);
            }

            // 添加监听地址
            array_map(function ($uri) use ($server) {
                $server->addListener($uri);
            }, $uris);

            return $server;
        });
    }

    /**
     * 注册HproseMenthodManage
     *
     * @return void
     */
    private function registerHproseMethodManage()
    {
        $this->app->singleton('hprose.router', function ($app) {
            return new \Zhuqipeng\LaravelHprose\Routing\Router;
        });
    }
}
