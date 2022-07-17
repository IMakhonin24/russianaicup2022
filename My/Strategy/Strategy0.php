<?php

use Debugging\DebugData\PlacedText;
use Debugging\DebugData\PolyLine;
use Model\Constants;
use Model\Unit;
use Model\UnitOrder;
use Model\Vec2;

require_once 'OrderStrategy.php';

class Strategy0 implements OrderStrategy
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
        $unitTargetPosition = new Vec2(0, 0);
        $zeroVectorVelocity = Helper::getPointOnLineABAtDistanceAC(new Vec2(0, 0), Helper::getVectorAB($this->unit->position, $unitTargetPosition), $this->constants->maxUnitForwardSpeed);

        $this->myTargetVelocity->setTargetVelocity($zeroVectorVelocity);
        if (!is_null($this->debugInterface)) {
            $this->debugInterface->add(new PolyLine([$this->unit->position, $unitTargetPosition], 0.1, MyColor::getColor(MyColor::GREEN_05)));//Line to TargetPosition
            $this->debugInterface->add(new PlacedText(Helper::getPointOnLineABAtDistanceAC($this->unit->position, $unitTargetPosition, 2.0), "TargetPosition", new Vec2(0, 0), 0.1, MyColor::getColor(MyColor::GREEN_1)));
            $this->debugInterface->add(new PolyLine([$this->unit->position, new Vec2($this->unit->position->x + $zeroVectorVelocity->x, $this->unit->position->y + $zeroVectorVelocity->y)], 0.1, MyColor::getColor(MyColor::BLUE_05)));//Line to Velocity
            $this->debugInterface->add(new PlacedText(Helper::getPointOnLineABAtDistanceAC($this->unit->position, $unitTargetPosition, 1.5), "Velocity", new Vec2(0, 0), 0.1, MyColor::getColor(MyColor::BLUE_1)));
        }

        $this->myTargetDirection->setTargetDirection(new Vec2(-$this->unit->direction->y, $this->unit->direction->x));
        $this->myActionOrder->setActionOrder(null);

        return new UnitOrder(
            $this->myTargetVelocity->getTargetVelocity(),
            $this->myTargetDirection->getTargetDirection(),
            $this->myActionOrder->getActionOrder(),
        );
    }

}