<?php

use Model\Constants;
use Model\Unit;
use Model\Vec2;

class MyTargetVelocity
{
    private Vec2 $targetVelocity;
    private ObstacleVelocityFilter $obstacleVelocityFilter;

    public function __construct(
        Unit $unit,
        MyObstacles $myObstacles,
        Constants $constants,
        ?DebugInterface $debugInterface
    )
    {
        $this->setTargetVelocity(new Vec2(0, 0));
        $this->obstacleVelocityFilter = new ObstacleVelocityFilter($unit, $myObstacles, $constants, $debugInterface);
    }

    public function setTargetVelocity(Vec2 $targetVelocity): void
    {
        $this->targetVelocity = $targetVelocity;
    }

    public function getTargetVelocity(): Vec2
    {
        return $this->obstacleVelocityFilter->getFilteredVelocity($this->targetVelocity);
    }
}