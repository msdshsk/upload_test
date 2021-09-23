<?php

namespace Shsk\Exception;

use Shsk\Exception as ExceptionInterface;

class FunctionException extends \ErrorException implements ExceptionInterface
{
    private static $backupLevel;

    public static function start()
    {
        self::$backupLevel = error_reporting(E_ALL);
        set_error_handler([__CLASS__, 'errorHandler']);
    }

    public static function end()
    {
        error_reporting(self::$backupLevel);
        restore_error_handler();
    }

    public static function errorHandler(int $severity, string $message, string $file, int $line): void
    {
        throw new self($message, 0, $severity, $file, $line);
    }
}
