<?php

namespace Zhuqipeng\LaravelHprose;

class HproseSwooleHttpServer extends \Hprose\Swoole\Http\Server
{
    /**
     * 初始化
     *
     * @author 朱其鹏 <28942998@qq.com>
     *
     * @param string|null $uri
     */
    public function __construct($uri = null, $mode = SWOOLE_BASE)
    {
        parent::__construct($uri, $mode);
    }
}
