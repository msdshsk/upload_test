<?php

namespace Shsk\Property;

use Shsk\Property as ParentProperty;
use Shsk\Property\Interfaces\Gettable;
use Shsk\Property\Interfaces\Arrayable;

class ReadOnly extends ParentProperty implements Gettable, Arrayable
{
}
