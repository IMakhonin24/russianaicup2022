<?php

use Model\Game;
use Model\Unit;

require_once 'CommonData.php';
require_once 'EveryTick.php';
require_once 'EveryUnit.php';

class MyDanger implements CommonData, EveryTick, EveryUnit
{
    const LEVEL_0 = 0;  //Низкий уровень опасности
    const LEVEL_1 = 1;  //Средний уровень опасности
    const LEVEL_2 = 2;  //Высокий уровень опасности

    /**
     * @var array | int[][]
     */
    private array $dangerLevelForMyUnit = [];

    public function __construct()
    {
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->loots = $game->loot;
    }

    public function everyTick(): void
    {
        $this->dangerLevelForMyUnit = [];
    }

    public function everyUnit($unit): void
    {
        $this->defineDangerLevel($unit);
    }

    private function defineDangerLevel(Unit $unit)
    {
    }

}