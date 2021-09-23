<?php

namespace Shsk;

use Shsk\Property\Traits\Accessor;

class Property
{
    use Accessor;

    public function __construct(array $ary)
    {
        $this->setProperties($ary);
    }
}
