<?php

namespace Zhuqipeng\LaravelHprose;

use stdClass;
use Exception;
use swoole_http_server;

class HproseSwooleHttpServer extends \Hprose\Swoole\Http\Service
{
    protected $server;
    protected $settings = [];
    protected $ons = [];
    protected $uris = [];
    protected $mode = SWOOLE_BASE;

    public function handle($request = null, $response = null)
    {
        $response->header('Access-Control-Allow-Origin', 'http://chongzhibao.biqu.tv');
        $response->header('Access-Control-Allow-Methods', '*');
        $response->header('Access-Control-Allow-Credentials', 'true');

        return parent::handle($request, $response);
    }

    public function __construct($uri, $mode = SWOOLE_BASE)
    {
        parent::__construct();
        $this->uris[] = $uri;
        $this->mode = $mode;
    }

    public function set($settings)
    {
        $this->settings = array_replace($this->settings, $settings);
    }

    public function on($name, $callback)
    {
        $this->ons[$name] = $callback;
    }

    public function addListener($uri)
    {
        $this->uris[] = $uri;
    }

    public function listen($host, $port, $type)
    {
        return $this->server->listen($host, $port, $type);
    }

    public function start()
    {
        $url = $this->parseUrl(array_shift($this->uris));
        $this->server = new swoole_http_server($url->host, $url->port, $this->mode, $url->type);

        foreach ($this->uris as $uri) {
            $url = $this->parseUrl($uri);
            $this->server->addListener($url->host, $url->port);
        }

        if (is_array($this->settings) && !empty($this->settings)) {
            $this->server->set($this->settings);
        }

        foreach ($this->ons as $on => $callback) {
            $this->server->on($on, $callback);
        }

        $this->httpHandle($this->server);

        $this->server->start();
    }

    private function parseUrl($uri)
    {
        $result = new stdClass();
        $p = parse_url($uri);
        if ($p) {
            switch (strtolower($p['scheme'])) {
                case 'http':
                    $result->host = $p['host'];
                    $result->port = isset($p['port']) ? $p['port'] : 80;
                    $result->type = SWOOLE_SOCK_TCP;
                    break;
                case 'https':
                    $result->host = $p['host'];
                    $result->port = isset($p['port']) ? $p['port'] : 443;
                    $result->type = SWOOLE_SOCK_TCP | SWOOLE_SSL;
                    break;
                default:
                    throw new Exception("Can't support this scheme: {$p['scheme']}");
            }
        } else {
            throw new Exception("Can't parse this uri: $uri");
        }
        return $result;
    }
}
