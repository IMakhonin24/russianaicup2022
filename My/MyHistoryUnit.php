<?php

use Model\Unit;

class MyHistoryUnit
{
    public Unit $unit;
    public int $tick;

    public function __construct(Unit $unit, $tick)
    {
        $this->unit = $unit;
        $this->tick = $tick;
    }
}