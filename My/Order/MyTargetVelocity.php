<?php

use Model\Vec2;

class MyTargetVelocity
{
    private Vec2 $targetVelocity;

    public function __construct(Vec2 $targetVelocity)
    {
        $this->setTargetVelocity($targetVelocity);
    }

    public function setTargetVelocity(Vec2 $targetVelocity): void
    {
        $this->targetVelocity = $targetVelocity;
    }

    public function getTargetVelocity(): Vec2
    {
        return $this->targetVelocity;
    }

}