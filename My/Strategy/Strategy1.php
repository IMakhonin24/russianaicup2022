<?php

use Debugging\DebugData\PlacedText;
use Debugging\DebugData\PolyLine;
use Model\Constants;
use Model\Unit;
use Model\UnitOrder;
use Model\Vec2;

require_once 'OrderStrategy.php';

class Strategy1 implements OrderStrategy
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

    public function __construct(
        Unit            $unit,
        MyObstacles     $myObstacles,
        MyLoot          $myLoot,
        MyUnit          $myUnit,
        MyProjectiles   $myProjectiles,
        MySound         $mySound,
        Constants       $constants,
        ?DebugInterface $debugInterface
    )
    {
        $this->unit = $unit;
        $this->myObstacles = $myObstacles;
        $this->myLoot = $myLoot;
        $this->myUnit = $myUnit;
        $this->myProjectiles = $myProjectiles;
        $this->mySound = $mySound;
        $this->constants = $constants;
        $this->debugInterface = $debugInterface;

        $this->myTargetVelocity = new MyTargetVelocity(new Vec2(0, 0));
        $this->myTargetDirection = new MyTargetDirection(new Vec2(0, 0));
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