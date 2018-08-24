<?php

namespace Zhuqipeng\LaravelHprose;

class HproseSocketServer extends \Hprose\Socket\Server
{
    /**
     * 初始化
     *
     * @author 朱其鹏 <28942998@qq.com>
     *
     * @param string|null $uri
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);

        // 置空父类uris，避免初始化传入的uri为空数据导致报错
        $this->uris = [];

        if ($uri) {
            $this->addListener($uri);
        }
    }
}
