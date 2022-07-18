<?php

use Model\Constants;
use Model\Game;
use Model\Order;
use Model\Unit;

require_once 'Model/Game.php';
require_once 'Model/Order.php';
require_once 'Model/Constants.php';

require_once 'My/MyColor.php';
require_once 'My/Helper.php';
require_once 'My/MyObstacles.php';
require_once 'My/MyProjectiles.php';
require_once 'My/MySound.php';
require_once 'My/MyLoot.php';
require_once 'My/MyUnit.php';
require_once 'My/MyAction.php';
require_once 'My/MyDanger.php';


require_once 'My/VelocityFilter.php';

require_once 'My/Order/MyTargetVelocity.php';
require_once 'My/Order/MyTargetDirection.php';
require_once 'My/Order/MyActionOrder.php';

require_once 'My/Strategy/MyStrategyData.php';
require_once 'My/Strategy/Strategy0.php';
require_once 'My/Strategy/Strategy1.php';
require_once 'My/Strategy/Strategy2.php';

class MyStrategy
{
    private MyObstacles $myObstacles;
    private MyLoot $myLoot;
    private MyUnit $myUnit;
    private MyProjectiles $myProjectiles;
    private MySound $mySound;
    private MyDanger $myDanger;

    private Constants $constants;

    function __construct(Constants $constants)
    {
        $this->constants = $constants;

        $this->myUnit = new MyUnit($constants);
        $this->myLoot = new MyLoot($constants);
        $this->myObstacles = new MyObstacles($constants);
        $this->myProjectiles = new MyProjectiles($constants);
        $this->mySound = new MySound($constants);
        $this->myDanger = new MyDanger($constants);
    }

    function getOrder(Game $game, ?DebugInterface $debugInterface): Order
    {
        $this->setCommonData($game, $debugInterface);
        $this->everyTick();

        $order = [];

        foreach ($this->myUnit->myUnits as $unit) {
            $this->everyUnit($unit);

            $strategyData = new MyStrategyData(
                $this->myObstacles,
                $this->myLoot,
                $this->myUnit,
                $this->myProjectiles,
                $this->mySound,
                $this->constants,
                $debugInterface
            );

            switch ($this->myDanger->getDangerLevel($unit)) {
                case MyDanger::LEVEL_0:
                    $strategy = new Strategy0($unit, $strategyData);
                    $order[$unit->id] = $strategy->getOrder();
                    break;
                case MyDanger::LEVEL_1:
                    $strategy = new Strategy1($unit, $strategyData);
                    $order[$unit->id] = $strategy->getOrder();
                    break;
                case MyDanger::LEVEL_2:
                    $strategy = new Strategy2($unit, $strategyData);
                    $order[$unit->id] = $strategy->getOrder();
                    break;
            }
        }

        return new Order($order);
    }

    private function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->myUnit->setCommonData($game, $debugInterface);
        $this->myLoot->setCommonData($game, $debugInterface);
        $this->myObstacles->setCommonData($game, $debugInterface);
        $this->myProjectiles->setCommonData($game, $debugInterface);
        $this->mySound->setCommonData($game, $debugInterface);
    }

    private function everyTick(): void
    {
        $this->myUnit->everyTick();
        $this->myLoot->everyTick();
        $this->myProjectiles->everyTick();
        $this->mySound->everyTick();
        $this->myDanger->everyTick();
    }

    private function everyUnit(Unit $unit): void
    {
        $this->myUnit->everyUnit($unit);
        $this->myLoot->everyUnit($unit);
        $this->myObstacles->everyUnit($unit);
        $this->myDanger->everyUnit($unit);
    }

    function debugUpdate(DebugInterface $debug_interface)
    {
    }

    function finish()
    {
    }
}