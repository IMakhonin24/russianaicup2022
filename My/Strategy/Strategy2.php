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

        $this->myTargetVelocity = new MyTargetVelocity(
            $unit,
            $this->myObstacles,
            $this->myProjectiles,
            $this->constants,
            $this->debugInterface
        );
        $this->myTargetDirection = new MyTargetDirection();
        $this->myActionOrder = new MyActionOrder();
    }


    public function getOrder(): UnitOrder
    {


        $this->debugInterface->add(new PlacedText(new Vec2($this->unit->position->x, $this->unit->position->y+1), "Pos = (".$this->unit->position->x.' ; '.$this->unit->position->y.')', new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));


        return new UnitOrder(
            new Vec2(100, 0),
            new Vec2(100, 0),
            null
        );
    }

}