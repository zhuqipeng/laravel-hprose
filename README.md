# Laravel-hprose

基于 [hprose/hprose-php](https://github.com/hprose/hprose-php/wiki) 开发的Laravel扩展：[laravel-hprose](https://github.com/zhuqipeng/laravel-hprose)

## 版本要求
```
Laravel>=5.2
```

## 安装
```shell
composer require "zhuqipeng/laravel-hprose:v1.0-alpha"
```
或者编辑composer.json
```json
"require": {
    "zhuqipeng/laravel-hprose": "v1.0-alpha"
}
```

## 配置
1. 在 config/app.php 注册 ServiceProvider 和 Facade (Laravel 5.5 无需手动注册)
    ```php
    'providers' => [
        // ...

        Zhuqipeng\LaravelHprose\ServiceProvider::class,
    ]
    ```
    ```php
    'aliases' => [
        // ...

        'LaravelHproseMethodManage' => Zhuqipeng\LaravelHprose\Facades\HproseMethodManage::class,
    ]
    ```
2. 配置.env文件
    监听地址列表，字符串json格式数组
    ```
    HPROSE_URIS=["tcp://0.0.0.0:1314"]
    ```

    是否启用demo方法，true开启 false关闭，开启后将自动对外发布一个远程调用方法 `demo`
    客户端可调用：$client->demo()
    ```
    HPROSE_DEMO=true // true or false
    ```

3. 创建`配置`和`路由`文件：
    ```shell
    php artisan vendor:publish --provider="Zhuqipeng\LaravelHprose\ServiceProvider"
    ```
    >应用根目录下的`config`目录下会自动生成新文件`hprose.php`
    >
    >应用根目录下的`routes`目录下会自动生成新文件`rpc.php`

## 使用

### 路由
>和 `laravel` 路由的用法相似，基于 [dingo/api](https://github.com/dingo/api) 的路由代码上做了简单修改

路由文件
```
routes/rpc.php
```

添加路由方法
```php
\LaravelHproseRouter::add(string $name, string|callable $action, array $options = []);
```
- string $name 可供客户端远程调用的方法名
- string|callable $action 类方法，格式：App\Controllers\User@update
- array $options 是一个关联数组，它里面包含了一些对该服务函数的特殊设置，详情请参考hprose-php官方文档介绍 [链接](https://github.com/hprose/hprose-php/wiki/06-Hprose-%E6%9C%8D%E5%8A%A1%E5%99%A8#addfunction-%E6%96%B9%E6%B3%95)

发布远程调用方法 `getUserByName` 和 `update`
```php
\LaravelHproseRouter::add('getUserByName', function ($name) {
    return 'name: ' . $name;
});

\LaravelHproseRouter::add('userUpdate', 'App\Controllers\User@update', ['model' => \Hprose\ResultMode::Normal]);
```

控制器
```php
<?php

namespace App\Controllers;

class User
{
    public function update($name)
    {
        return 'update name: ' . $name;
    }
}
```

客户端调用
```php
$client->getUserByName('zhuqipeng');
$client->userUpdate('zhuqipeng');
```

路由组
```php
\LaravelHproseRouter::group(array $attributes, callable $callback);
```
- array $attributes 属性 ['namespace' => '', 'prefix' => '']
- callable $callback 回调函数

```php
\LaravelHproseRouter::group(['namespace' => 'App\Controllers'], function ($route) {
    $route->add('getUserByName', function ($name) {
        return 'name: ' . $name;
    });

    $route->add('userUpdate', 'User@update');
});
```
客户端调用
```php
$client->getUserByName('zhuqipeng');
$client->userUpdate('zhuqipeng');
```

前缀
```php
\LaravelHproseRouter::group(['namespace' => 'App\Controllers', 'prefix' => 'user'], function ($route) {
    $route->add('getByName', function ($name) {
        return 'name: ' . $name;
    });

    $route->add('update', 'User@update');
});
```
客户端调用
```php
$client->user->getByName('zhuqipeng');
$client->user->update('zhuqipeng');
// 或者
$client->user_getByName('zhuqipeng');
$client->user_update('zhuqipeng');
```

### 启动服务

```shell
php artisan hprose:socket_server
```