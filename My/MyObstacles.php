<?php

use Model\Constants;
use Model\Game;
use Model\Obstacle;
use Model\Unit;

require_once 'CommonData.php';
require_once 'EveryUnit.php';

class MyObstacles implements CommonData, EveryUnit
{
    private Constants $constants;
    private ?DebugInterface $debugInterface;

    /**
     * @var array | Obstacle[]
     */
    public array $obstacles; //Массив препятствий

    public function __construct(Constants $constants)
    {
        $this->constants = $constants;
        $this->defineObstaclesMap($constants);
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->debugInterface = $debugInterface;
    }

    public function everyUnit(Unit $unit): void
    {
    }

    private function defineObstaclesMap(Constants $constants)
    {
        $this->obstacles = [];
        foreach ($constants->obstacles as $obstacle) {
            $this->obstacles[] = $obstacle;
        }
    }
}