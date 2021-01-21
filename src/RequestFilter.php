<?php

namespace Ebcms;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestFilter
{

    private $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    public function has(string $str): bool
    {
        $arr = array_filter(explode('.', $str));
        if (!isset($arr[1])) {
            return false;
        }
        $type = array_shift($arr);
        switch ($type) {
            case 'get':
                return $this->isSetValue($this->getServerRequest()->getQueryParams(), $arr);
                break;

            case 'post':
                return $this->isSetValue($this->getServerRequest()->getParsedBody(), $arr);
                break;

            case 'cookie':
                return $this->isSetValue($this->getServerRequest()->getCookieParams(), $arr);
                break;

            case 'attr':
                return $this->isSetValue($this->getServerRequest()->getAttributes(), $arr);
                break;

            default:
                return false;
                break;
        }
    }

    public function get(string $field = null, $default = null, array $filters = ['self::defaultFilter'])
    {
        return $this->getFilterValue($this->getServerRequest()->getQueryParams(), $field, $default, $filters);
    }

    public function post(string $field = null, $default = null, array $filters = ['self::defaultFilter'])
    {
        return $this->getFilterValue($this->getServerRequest()->getParsedBody(), $field, $default, $filters);
    }

    public function cookie(string $field = null, $default = null, array $filters = ['self::defaultFilter'])
    {
        return $this->getFilterValue($this->getServerRequest()->getCookieParams(), $field, $default, $filters);
    }

    public function attr(string $field = null, $default = null, array $filters = ['self::defaultFilter'])
    {
        return $this->getFilterValue($this->getServerRequest()->getAttributes(), $field, $default, $filters);
    }

    private function getFilterValue(array $data, ?string $field, $default, array $filters = [])
    {
        $value = $this->getValue($data, array_filter(explode('.', $field)), $default);
        if ($filters) {
            return $this->filter($value, $filters);
        }
        return $value;
    }

    private function filter($value, array $filters = [])
    {
        foreach ($filters as $filter) {
            if (is_callable($filter)) {
                $value = call_user_func($filter, $value);
            }
        }
        return $value;
    }

    private function isSetValue(array $data = [], array $arr = []): bool
    {
        $key = array_shift($arr);
        if (!$arr) {
            return isset($data[$key]);
        }
        if (!isset($data[$key])) {
            return false;
        }
        return $this->isSetValue($data[$key], $arr);
    }

    private function getValue($data = [], array $arr = [], $default = null)
    {
        if (!$arr) {
            return $data;
        }
        if (!is_array($data)) {
            return $default;
        }
        $key = array_shift($arr);
        if (!$arr) {
            return isset($data[$key]) ? $data[$key] : $default;
        }
        if (!isset($data[$key])) {
            return $default;
        }
        return $this->getValue($data[$key], $arr, $default);
    }

    public static function defaultFilter($value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = self::defaultFilter($v);
            }
        } else {
            $value = htmlspecialchars($value);
        }
        return $value;
    }

    private function getServerRequest(): ServerRequestInterface
    {
        return $this->container->get(ServerRequestInterface::class);
    }
}
