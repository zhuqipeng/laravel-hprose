<?php

namespace Zhuqipeng\LaravelHprose\Routing;

class Router
{
    protected $groupStack = [];

    protected $methods = [];

    protected $prefix = '';

    /**
     * 创建一组方法
     *
     * @param array $attributes
     * @param callable $callback
     *
     * @return void
     */
    public function group(array $attributes, callable $callback)
    {
        $attributes = $this->mergeLastGroupAttributes($attributes);

        if ((!isset($attributes['prefix']) || empty($attributes['prefix'])) && isset($this->prefix)) {
            $attributes['prefix'] = $this->prefix;
        }

        $this->groupStack[] = $attributes;

        call_user_func($callback, $this);

        array_pop($this->groupStack);
    }

    /**
     * 添加方法
     *
     * @param string $name
     * @param string|callable $action
     * @param array $options
     *  是一个关联数组，它里面包含了一些对该服务函数的特殊设置，详情参考hprose-php文档介绍
     *  https://github.com/hprose/hprose-php/wiki/06-Hprose-%E6%9C%8D%E5%8A%A1%E5%99%A8#addfunction-%E6%96%B9%E6%B3%95
     *
     * @return void
     */
    public function add(string $name, $action, array $options = [])
    {
        if (is_string($action)) {
            $action = ['controller' => $action, 'type' => 'method'];
        } elseif (is_callable($action)) {
            $action = ['callable' => $action, 'type' => 'callable'];
        }

        $action = $this->mergeLastGroupAttributes($action);

        if (!empty($action['prefix'])) {
            $name = ltrim(rtrim(trim($action['prefix'], '_') . '_' . trim($name, '_'), '_'), '_');
        }

        switch ($action['type']) {
            case 'method':
                list($class, $method) = $this->parseController($action['namespace'], $action['controller']);

                $this->addMethod($method, $class, $name, $options);
                break;

            case 'callable':
                $this->addFunction($action['callable'], $name, $options);
                break;
        }
    }

    /**
     * 获取所有已添加方法列表
     *
     * @return array
     */
    public function getMethods()
    {
        return $this->methods;
    }

    /**
     * 合并最后一组属性
     *
     * @param array $attributes
     *
     * @return array
     */
    protected function mergeLastGroupAttributes(array $attributes)
    {
        if (empty($this->groupStack)) {
            return $this->mergeGroup($attributes, []);
        }

        return $this->mergeGroup($attributes, end($this->groupStack));
    }

    /**
     * 合并新加入的组
     *
     * @param array $new
     * @param array $old
     *
     * @return array
     */
    protected function mergeGroup(array $new, array $old)
    {
        $new['namespace'] = $this->formatNamespace($new, $old);
        $new['prefix'] = $this->formatPrefix($new, $old);

        return array_merge_recursive(array_except($old, ['namespace', 'prefix']), $new);
    }

    /**
     * 格式化命名空间
     *
     * @param array $new
     * @param array $old
     *
     * @return string
     */
    protected function formatNamespace(array $new, array $old)
    {
        if (isset($new['namespace']) && isset($old['namespace'])) {
            return trim($old['namespace'], '\\') . '\\' . trim($new['namespace'], '\\');
        } elseif (isset($new['namespace'])) {
            return trim($new['namespace'], '\\');
        }

        return array_get($old, 'namespace');
    }

    /**
     * 解析控制器
     *
     * @param string|null $namespace
     * @param string $controller
     *
     * @return array
     */
    protected function parseController($namespace, string $controller): array
    {
        list($classAsStr, $method) = explode('@', $controller);

        $refClass = new \ReflectionClass(
            join('\\', array_filter([$namespace, $classAsStr]))
        );

        $class = $refClass->newInstance();

        return [$class, $method];
    }


    /**
     * 格式化前缀
     *
     * @param array $new
     * @param array $old
     *
     * @return string
     */
    protected function formatPrefix(array $new, array $old)
    {
        if (isset($new['prefix'])) {
            return trim(array_get($old, 'prefix'), '_') . '_' . trim($new['prefix'], '_');
        }

        return array_get($old, 'prefix', '');
    }

    /**
     * 添加匿名函数
     *
     * @param callable $action
     * @param string $alias
     * @param array $options
     *
     * @return void
     */
    private function addFunction(callable $action, string $alias, array $options)
    {
        $this->methods[] = $alias;

        app('hprose.socket_server')->addFunction($action, $alias, $options);
    }

    /**
     * 添加类方法
     *
     * @param string $method
     * @param object $class
     * @param string $alias
     * @param array $alias
     *
     * @return void
     */
    private function addMethod(string $method, $class, string $alias, array $options)
    {
        $this->methods[] = $alias;

        app('hprose.socket_server')->addMethod(
            $method,
            $class,
            $alias,
            $options
        );
    }
}
