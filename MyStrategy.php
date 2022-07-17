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
require_once 'My/MyOrder.php';

class MyStrategy
{
    private MyObstacles $myObstacles;
    private MyLoot $myLoot;
    private MyUnit $myUnit;
    private MyProjectiles $myProjectiles;
    private MySound $mySound;
    private MyOrder $myOrder;

    function __construct(Constants $constants)
    {
        $this->myUnit = new MyUnit($constants);
        $this->myLoot = new MyLoot($constants);
        $this->myObstacles = new MyObstacles($constants);
        $this->myProjectiles = new MyProjectiles($constants);
        $this->mySound = new MySound($constants);
        $this->myOrder = new MyOrder($constants);
    }

    function getOrder(Game $game, ?DebugInterface $debugInterface): Order
    {
        $this->setCommonData($game, $debugInterface);
        $this->everyTick();

        foreach ($this->myUnit->myUnits as $unit) {
            $this->everyUnit($unit);
        }

        return new Order($this->myOrder->getOrderForMyUnits());
    }

    private function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->myUnit->setCommonData($game, $debugInterface);
        $this->myLoot->setCommonData($game, $debugInterface);
        $this->myObstacles->setCommonData($game, $debugInterface);
        $this->myProjectiles->setCommonData($game, $debugInterface);
        $this->mySound->setCommonData($game, $debugInterface);
        $this->myOrder->setCommonData($game, $debugInterface);
    }

    private function everyTick(): void
    {
        $this->myUnit->everyTick();
        $this->myLoot->everyTick();
        $this->myProjectiles->everyTick();
        $this->mySound->everyTick();
    }

    private function everyUnit(Unit $unit): void
    {
        $this->myUnit->everyUnit($unit);
        $this->myLoot->everyUnit($unit);
        $this->myObstacles->everyUnit($unit);
        $this->myOrder->everyUnit($unit);
    }

    function debugUpdate(DebugInterface $debug_interface)
    {
    }

    function finish()
    {
    }
}