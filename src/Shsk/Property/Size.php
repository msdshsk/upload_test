<?php

namespace Shsk\Property;

class Size extends ReadOnly
{
    public function __construct($width, $height)
    {
        parent::__construct(compact('width', 'height'));
    }

    public function max()
    {
        return $this->width > $this->height ? $this->width : $this->height;
    }

    public function min()
    {
        return $this->width < $this->height ? $this->width : $this->height;
    }
}
