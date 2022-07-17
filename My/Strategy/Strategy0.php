<?php

use Debugging\DebugData\PlacedText;
use Debugging\DebugData\PolyLine;
use Model\Constants;
use Model\Game;
use Model\Unit;
use Model\UnitOrder;
use Model\Vec2;

require_once 'OrderStrategy.php';

class Strategy0 implements CommonData, OrderStrategy
{
    private Constants $constants;
    private Game $game;
    private ?DebugInterface $debugInterface = null;

    private MyTargetVelocity $myTargetVelocity;
    private MyTargetDirection $myTargetDirection;
    private MyActionOrder $myActionOrder;

    private Unit $unit;

    public function __construct(Unit $unit)
    {
        $this->unit = $unit;

        $this->myTargetVelocity = new MyTargetVelocity(new Vec2(0, 0));
        $this->myTargetDirection = new MyTargetDirection(new Vec2(0, 0));
        $this->myActionOrder = new MyActionOrder();
    }

    public function setConstants(Constants $constants): void
    {
        $this->constants = $constants; //todo interface??
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->debugInterface = $debugInterface;
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