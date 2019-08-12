<?php

namespace Zhuqipeng\LaravelHprose\Commands;

class HproseSwooleHttp extends Base
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hprose:swoole_http_server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'swoole_http 服务';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->outputInfo('http');

        $server = app('hprose.swoole_http_server');

        $server->start();
    }
}
