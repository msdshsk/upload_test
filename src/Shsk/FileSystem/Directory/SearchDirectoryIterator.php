<?php

namespace Shsk\FileSystem\Directory;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Shsk\FileSystem\Directory;

class SearchDirectoryIterator extends RecursiveIteratorIterator
{
    private $keyword = null;
    private $deep;
    private $currentDepth;
    private $currentDir;
    public function __construct($dir, $keyword = null, bool $deep = true)
    {
        $this->keyword = $keyword;
        $this->deep = $deep;
        $this->currentDir = Directory::cleanPath($dir, false);

        if ($deep === false) {
            $this->currentDepth = substr_count($this->currentDir, DIRECTORY_SEPARATOR);
        }

        parent::__construct(new RecursiveDirectoryIterator($this->currentDir));
    }

    public function next()
    {
        parent::next();
        if (!parent::valid()) {
            return;
        }
        while (false === $this->validateKeyword(parent::current())) {
            parent::next();
            if (!parent::valid()) {
                break;
            }
        }
    }

    public function rewind()
    {
        parent::rewind();
        if (!parent::valid()) {
            return;
        }

        if (!$this->validateKeyword(parent::current())) {
            $this->next();
        }
    }

    private function validateKeyword(\SplFileInfo $file): bool
    {
        if ($file->getFilename() !== '.') {
            return false;
        }

        if (!$file->isDir()) {
            return false;
        }

        if ($file->getPath() === $this->currentDir) {
            return false;
        }

        $valid = true;
        if ($this->deep === false) {
            $valid = substr_count($file->getPath(), DIRECTORY_SEPARATOR) - 1 === $this->currentDepth;
        }

        if ($this->keyword === null) {
            return $valid;
        }

        if ($valid === true) {
            if (is_callable($this->keyword)) {
                return call_user_func($this->keyword, $file);
            }

            if (true === (bool) preg_match($this->keyword, $file->getPath(), $match)) {
                return true;
            }
        }

        return false;
    }
}
