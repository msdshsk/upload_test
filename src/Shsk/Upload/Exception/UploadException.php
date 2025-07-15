<?php

namespace Shsk\Upload\Exception;

class UploadException extends \Exception
{
    public function __construct(string $message, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
} 