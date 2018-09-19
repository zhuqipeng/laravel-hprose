<?php

namespace Zhuqipeng\LaravelHprose\Routing;

class Router
{
    protected $groupStack = [];

    protected $methods = [];

    protected $prefix = '';

    protected $lastMethodName = '';

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
     * @return $this
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
                $this->mapRefMethodParameterName($class, $method, $name);
                break;

            case 'callable':
                $this->addFunction($action['callable'], $name, $options);
                $this->mapRefFuncParameterName($action['callable'], $name);
                break;
        }

        $this->appendMethod($name);
        $this->setLastMethodName($name);

        return $this;
    }

    /**
     * 添加参数验证器
     *
     * @param string $value
     *
     * @return void
     */
    public function parameter(string $value)
    {
        if ($value) {
            $c = join('\\', [config('hprose.parameter'), $value]);

            app('hprose.parameter')->mapParameter($this->getLastMethodName(), (new $c));
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
     * 追加至已添加方法列表
     *
     * @param string $methodName
     *
     * @return void
     */
    private function appendMethod(string $methodName)
    {
        $this->methods[] = $methodName;
    }

    private function setLastMethodName(string $methodName)
    {
        $this->lastMethodName = $methodName;
    }

    private function getLastMethodName()
    {
        return $this->lastMethodName;
    }

    /**
     * 合并最后一组属性
     *
     * @param array $attributes
     *
     * @return array
     */
    private function mergeLastGroupAttributes(array $attributes)
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
    private function mergeGroup(array $new, array $old)
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
    private function formatNamespace(array $new, array $old)
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
    private function parseController($namespace, string $controller): array
    {
        $namespace = $namespace ? $namespace : config('hprose.controller');

        list($classAsStr, $method) = explode('@', $controller);

        $class = resolve(join('\\', array_filter([$namespace, $classAsStr])));

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
    private function formatPrefix(array $new, array $old)
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
        app('hprose.socket_server')->addMethod($method, $class, $alias, $options);
    }

    /**
     * 关联函数或方法的参数名
     *
     * @param object $class
     * @param string $method
     * @param string $alias
     * @return void
     */
    private function mapRefMethodParameterName($class, string $method, string $alias)
    {
        $ref = new \ReflectionMethod($class, $method);

        app('hprose.parameter')->mapParameterName($alias, array_map(function ($parameter) {
            return $parameter->name;
        }, $ref->getParameters()));
    }

    /**
     * 关联函数或方法的参数名
     *
     * @param callable $callback
     * @param string $alias
     * @return void
     */
    private function mapRefFuncParameterName(callable $callback, string $alias)
    {
        $ref = new \ReflectionFunction($callback);

        app('hprose.parameter')->mapParameterName($alias, array_map(function ($parameter) {
            return $parameter->name;
        }, $ref->getParameters()));
    }
}
