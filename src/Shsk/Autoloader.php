<?php

namespace Shsk;

class Autoloader
{
    private $rootDir;

    private static $instances = [];

    public function __construct($namespace = __NAMESPACE__, $rootDir = __DIR__)
    {
        $this->namespace = $namespace;
        $this->rootDir = $rootDir;

        if ($this->register()) {
            self::$instances[$namespace] = $this;
        }
    }

    private function isRegisted()
    {
        return isset(self::$instances[$this->namespace]);
    }

    private function register()
    {
        if ($this->isRegisted()) {
            return false;
        }
        return spl_autoload_register([$this, 'include']);
    }

    public function include($class)
    {
        if (strpos($class, $this->namespace . '\\') === 0) {
            $class = substr($class, strlen($this->namespace));
            require_once $this->rootDir . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
        }
    }
}
