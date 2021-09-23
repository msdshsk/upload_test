<?php

namespace Shsk\Property;

use Shsk\Property as ParentProperty;
use Shsk\Property\Interfaces\Settable;
use Shsk\Property\Interfaces\Gettable;
use Shsk\Property\Interfaces\Arrayable;

class Basis extends ParentProperty implements Settable, Gettable, Arrayable
{
}
