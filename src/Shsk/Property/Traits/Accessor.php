<?php

namespace Shsk\Property\Traits;

use Shsk\Exception\Exception;
use Shsk\Property\Interfaces\Settable;
use Shsk\Property\Interfaces\Gettable;
use Shsk\Property\Interfaces\Arrayable;

trait Accessor
{
    private $props = [];

    protected function setProperty($name, $value)
    {
        $this->props[$name] = $value;
    }

    protected function setProperties(array $props)
    {
        foreach ($props as $key => $value) {
            if (is_array($value)) {
                $this->setProperty($key, new self($value));
            } else {
                $this->setProperty($key, $value);
            }
        }
    }

    public function get($name, $default = null)
    {
        if (!($this instanceof Gettable)) {
            throw new Exception("can't set property");
        }

        if ($this->has($name)) {
            return $this->props[$name] ?? $default;
        }
        throw new Exception('unknown property :' . $name);
    }

    public function __get($name)
    {
        return $this->get($name);
    }

    public function set($name, $value)
    {
        if (!($this instanceof Settable)) {
            throw new Exception("can't set property");
        }

        if (!$this->has($name)) {
            throw new Exception('unknown property :' . $name);
        }

        $this->props[$name] = $value;
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    public function has($name)
    {
        return array_key_exists($name, $this->props);
    }

    public function __isset($name)
    {
        return $this->has($name);
    }

    public function toArray()
    {
        $results = [];
        foreach ($this->props as $key => $value) {
            if ($value instanceof Arrayable) {
                $results[$key] = $value->toArray();
            } else {
                $results[$key] = $value;
            }
        }
        return $results;
    }
}
