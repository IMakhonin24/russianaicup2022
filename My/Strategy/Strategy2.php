<?php

use Debugging\DebugData\PlacedText;
use Debugging\DebugData\PolyLine;
use Model\Constants;
use Model\Unit;
use Model\UnitOrder;
use Model\Vec2;

require_once 'OrderStrategy.php';

class Strategy2 implements OrderStrategy
{
    private MyObstacles $myObstacles;
    private MyLoot $myLoot;
    private MyUnit $myUnit;
    private MyProjectiles $myProjectiles;
    private MySound $mySound;
    private Constants $constants;
    private ?DebugInterface $debugInterface;

    private MyTargetVelocity $myTargetVelocity;
    private MyTargetDirection $myTargetDirection;
    private MyActionOrder $myActionOrder;

    private Unit $unit;

    public function __construct(Unit $unit, MyStrategyData $myStrategyData)
    {
        $this->unit = $unit;

        $this->myObstacles = $myStrategyData->myObstacles;
        $this->myLoot = $myStrategyData->myLoot;
        $this->myUnit = $myStrategyData->myUnit;
        $this->myProjectiles = $myStrategyData->myProjectiles;
        $this->mySound = $myStrategyData->mySound;
        $this->constants = $myStrategyData->constants;
        $this->debugInterface = $myStrategyData->debugInterface;

        $this->myTargetVelocity = new MyTargetVelocity($this->myObstacles);
        $this->myTargetDirection = new MyTargetDirection();
        $this->myActionOrder = new MyActionOrder();
    }


    public function getOrder(): UnitOrder
    {
        return new UnitOrder(
            new Vec2(-$this->unit->position->x, -$this->unit->position->y),
            new Vec2(-$this->unit->direction->y, $this->unit->direction->x),
            new Model\ActionOrder\Aim(true)
        );
    }

}