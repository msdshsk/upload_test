<?php

namespace Shsk\FileSystem\Directory;

class SearchExtensionIterator extends SearchFileIterator
{
    public function __construct($dir, array $extensions, bool $deep = true)
    {
        $code = implode('|', $extensions);
        parent::__construct($dir, "/\\.(?:{$code})$/i", $deep);
    }
}
