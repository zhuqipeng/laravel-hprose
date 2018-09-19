<?php

namespace Zhuqipeng\LaravelHprose\Parameters;

use Zhuqipeng\LaravelHprose\Parameters\Base;

class Manage
{
    protected $map = [];

    /**
     * 关联方法和参数验证器
     *
     * @param string $methodName
     * @param Base $parameter
     *
     * @return void
     */
    public function mapParameter(string $methodName, Base $parameter)
    {
        $this->map[$methodName]['parameter'] = $parameter;
    }

    /**
     * 关联方法和参数名
     *
     * @param string $methodName
     * @param array $names
     *
     * @return void
     */
    public function mapParameterName(string $methodName, array $names)
    {
        $this->map[$methodName]['parameterNames'] = $names;
    }

    /**
     * 执行验证操作
     *
     * @param string $methodName
     * @param array $args

     * @return mixed
     */
    public function validationFails(string $methodName, array $args)
    {
        $errors = null;

        if ($map = array_get($this->map, $methodName)) {
            $parameterNames = array_get($map, 'parameterNames', []);
            $validationData = $this->combine($parameterNames, $args);
            if ($parameter = array_get($map, 'parameter')) {
                $validate = \Validator::make($validationData, $parameter->rules(), $parameter->messages());
                if ($validate->fails()) {
                    $errors = $parameter->formatErrors($validate->errors());
                }
            }
        }

        return $errors;
    }

    /**
     * 结合方法或函数的参数名和值
     *
     * @param array $parameterNames
     * @param array $args
     *
     * @return array
     */
    private function combine(array $parameterNames, array $args)
    {
        $combine = [];

        foreach ($parameterNames as $index => $name) {
            if (array_key_exists($index, $args)) {
                $combine[$name] = $args[$index];
            }
        }

        return $combine;
    }
}
