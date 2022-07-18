<?php

use Model\Constants;
use Model\Unit;
use Model\Vec2;

class MyTargetVelocity
{
    private Vec2 $targetVelocity;
    private ObstacleVelocityFilter $obstacleVelocityFilter;
    private ProjectileVelocityFilter $projectileVelocityFilter;

    public function __construct(
        Unit $unit,
        MyObstacles $myObstacles,
        MyProjectiles $myProjectiles,
        Constants $constants,
        ?DebugInterface $debugInterface
    )
    {
        $this->setTargetVelocity(new Vec2(0, 0));
        $this->obstacleVelocityFilter = new ObstacleVelocityFilter($unit, $myObstacles, $constants, $debugInterface);
        $this->projectileVelocityFilter = new ProjectileVelocityFilter($unit, $myProjectiles, $myObstacles, $constants, $debugInterface);
    }

    public function setTargetVelocity(Vec2 $targetVelocity): void
    {
        $this->targetVelocity = $targetVelocity;
    }

    public function getTargetVelocity(): Vec2
    {
        $this->targetVelocity = $this->projectileVelocityFilter->getFilteredVelocity($this->targetVelocity);
        $this->targetVelocity = $this->obstacleVelocityFilter->getFilteredVelocity($this->targetVelocity);
        return $this->targetVelocity;
    }
}