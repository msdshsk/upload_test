<?php

namespace Shsk\FileSystem;

use SplFileInfo;

class File extends SplFileInfo
{
    public function put($data, $flags = 0)
    {
        if ($this->isWritable()) {
            return file_put_contents($this->getPathname(), $data, $flags);
        }
        return false;
    }

    public function write($data, $flags = 0)
    {
        return $this->put($data, $flags);
    }

    public function get($offset = 0, $length = null)
    {
        if ($this->isReadable()) {
            return file_get_contents($this->getPathname(), false, null, $offset, $length);
        }
        return false;
    }

    public function read($offset = 0, $length = null)
    {
        return $this->get($offset, $length);
    }

    public function exists()
    {
        return $this->isFile();
    }

    public function unlink()
    {
        if ($this->isWritable()) {
            return unlink($this->getPathname());
        }
        return false;
    }

    public function getBase()
    {
        return $this->getBasename('.' . $this->getExtension());
    }

    public function canRead()
    {
        return $this->exists() && $this->getType() === 'file' && $this->isReadable();
    }

    public function canWrite()
    {
        return $this->exists() && $this->getType() === 'file' && $this->isWritable();
    }
}
