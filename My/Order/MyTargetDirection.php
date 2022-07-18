<?php

use Model\Vec2;

class MyTargetDirection
{
    private Vec2 $targetDirection;

    public function __construct()
    {
        $this->setTargetDirection(new Vec2(0, 0));
    }

    public function setTargetDirection(Vec2 $targetDirection): void
    {
        $this->targetDirection = $targetDirection;
    }

    public function getTargetDirection(): Vec2
    {
        return $this->targetDirection;
    }

}