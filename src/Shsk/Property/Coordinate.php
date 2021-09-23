<?php

namespace Shsk\Property;

class Coordinate extends ReadOnly
{
    public function __construct($x, $y)
    {
        parent::__construct(['x' => $x, 'y' => $y]);
    }

    public function max()
    {
        return $this->x > $this->y ? $this->x : $this->y;
    }

    public function min()
    {
        return $this->x < $this->y ? $this->x : $this->y;
    }

    public function ajast(int|Coordinate $x, int $y = null)
    {
        if ($x instanceof Coordinate) {
            $ajast_x = $this->x + $x->x;
            $ajast_y = $this->y + $x->y;
        } else {
            $ajast_x = $this->x + $x;
            $ajast_y = $this->y + $y;
        }
        return new self($ajast_x, $ajast_y);
    }
}
