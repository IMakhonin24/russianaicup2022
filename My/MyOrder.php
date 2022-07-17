<?php

use Model\Constants;
use Model\Game;
use Model\Unit;

require_once 'Order/MyTargetVelocity.php';
require_once 'Order/MyTargetDirection.php';
require_once 'Order/MyActionOrder.php';
require_once 'MyDanger.php';
require_once 'Strategy/Strategy0.php';

class MyOrder implements CommonData, EveryUnit
{
    private Constants $constants;
    private Game $game;
    private ?DebugInterface $debugInterface = null;

    private array $order = [];

    public function __construct(Constants $constants)
    {
        $this->constants = $constants;
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->game = $game;
        $this->debugInterface = $debugInterface;
    }

    public function everyUnit(Unit $unit): void
    {
        $this->defineOrderForMyUnits($unit);
    }

    private function defineOrderForMyUnits(Unit $unit): void
    {
        $dangerLevel = MyDanger::LEVEL_0;
        switch ($dangerLevel) {
            case MyDanger::LEVEL_0:
                $strategy = new Strategy0($unit);
                $strategy->setCommonData($this->game, $this->debugInterface);
                $strategy->setConstants($this->constants);
                $this->order[$unit->id] = $strategy->getOrder();
                break;
            case MyDanger::LEVEL_1:
                $strategy = new Strategy0($unit);
                $strategy->setCommonData($this->game, $this->debugInterface);
                $strategy->setConstants($this->constants);
                $this->order[$unit->id] = $strategy->getOrder();
                break;
            case MyDanger::LEVEL_2:
                $strategy = new Strategy0($unit);
                $strategy->setCommonData($this->game, $this->debugInterface);
                $strategy->setConstants($this->constants);
                $this->order[$unit->id] = $strategy->getOrder();
                break;
        }
    }

    public function getOrderForMyUnits(): array
    {
        return $this->order;
    }
}