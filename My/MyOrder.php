<?php

use Debugging\DebugData\PlacedText;
use Debugging\DebugData\PolyLine;
use Model\Constants;
use Model\Game;
use Model\Unit;
use Model\UnitOrder;
use Model\Vec2;

require_once 'Order/MyTargetVelocity.php';
require_once 'Order/MyTargetDirection.php';
require_once 'Order/MyActionOrder.php';

class MyOrder implements CommonData, EveryTick, EveryUnit
{
    private ?DebugInterface $debugInterface = null;
    private float $maxUnitForwardSpeed;

    private array $order = [];

    private MyTargetVelocity $myTargetVelocity;
    private MyTargetDirection $myTargetDirection;
    private MyActionOrder $myActionOrder;

    public function __construct(Constants $constants)
    {
        $this->maxUnitForwardSpeed = $constants->maxUnitForwardSpeed;
        $this->myTargetVelocity = new MyTargetVelocity(new Vec2(0, 0));
        $this->myTargetDirection = new MyTargetDirection(new Vec2(0, 0));
        $this->myActionOrder = new MyActionOrder();
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->debugInterface = $debugInterface;
    }

    public function everyTick(): void
    {
    }

    public function everyUnit(Unit $unit): void
    {
        $this->defineOrderForMyUnits($unit);
    }

    private function defineOrderForMyUnits(Unit $unit): void
    {
        $unitTargetPosition = new Vec2($unit->position->x + 50, $unit->position->y);
        $zeroVectorVelocity = Helper::getPointOnLineABAtDistanceAC(new Vec2(0, 0), Helper::getVectorAB($unit->position, $unitTargetPosition), $this->maxUnitForwardSpeed);

        $this->myTargetVelocity->setTargetVelocity($zeroVectorVelocity);
        if (!is_null($this->debugInterface)) {
            $this->debugInterface->add(new PolyLine([$unit->position, $unitTargetPosition], 0.1, MyColor::getColor(MyColor::GREEN_05)));//Line to TargetPosition
            $this->debugInterface->add(new PlacedText(Helper::getPointOnLineABAtDistanceAC($unit->position, $unitTargetPosition, 2.0), "TargetPosition", new Vec2(0, 0), 0.1, MyColor::getColor(MyColor::GREEN_1)));
            $this->debugInterface->add(new PolyLine([$unit->position, new Vec2($unit->position->x + $zeroVectorVelocity->x, $unit->position->y + $zeroVectorVelocity->y)], 0.1, MyColor::getColor(MyColor::BLUE_05)));//Line to Velocity
            $this->debugInterface->add(new PlacedText(Helper::getPointOnLineABAtDistanceAC($unit->position, $unitTargetPosition, 1.5), "Velocity", new Vec2(0, 0), 0.1, MyColor::getColor(MyColor::BLUE_1)));
        }

        $this->myTargetDirection->setTargetDirection(new Vec2(-$unit->direction->y, $unit->direction->x));
        $this->myActionOrder->setActionOrder(null);

        $this->order[$unit->id] = new UnitOrder(
            $this->myTargetVelocity->getTargetVelocity(),
            $this->myTargetDirection->getTargetDirection(),
            $this->myActionOrder->getActionOrder()
        );
    }

    public function getOrderForMyUnits(): array
    {
        return $this->order;
    }
}