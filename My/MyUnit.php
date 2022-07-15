<?php

use Debugging\DebugData\Circle;
use Debugging\DebugData\PlacedText;
use Model\Constants;
use Model\Game;
use Model\Unit;
use Model\Vec2;

require_once 'CommonData.php';
require_once 'EveryTick.php';
require_once 'EveryUnit.php';
require_once 'MyCommonConst.php';
require_once 'MyHistoryUnit.php';
require_once 'Loot/MyWeapon.php';
require_once 'Loot/MyPot.php';
require_once 'Loot/MyAmmo.php';

class MyUnit implements CommonData, EveryTick, EveryUnit
{
    private float $unitRadius;

    private ?DebugInterface $debugInterface = null;
    private int $myPlayerId = 0;
    private int $currentTick = 0;

    /**
     * @var array | Unit[]
     */
    private array $units = []; //todo unset

    /**
     * @var array | Unit[]
     */
    public array $myUnits = [];

    /**
     * @var array | Unit[]
     */
    public array $enemies = [];

    /**
     * @var array | MyHistoryUnit[]
     */
    private array $historyEnemies = []; //История примерного местоположения врагов

    /**
     * @var array | Unit[]
     */
    private array $targetEnemyForMyUnits = [];

    /**
     * @var array | Unit[][]
     */
    public array $enemyInPersonalAreaForMyUnits;

    public function __construct(Constants $constants)
    {
        $this->unitRadius = $constants->unitRadius;
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->units = $game->units;
        $this->myPlayerId = $game->myId;
        $this->currentTick = $game->currentTick;
        $this->debugInterface = $debugInterface;
    }

    public function everyTick(): void
    {
        $this->targetEnemyForMyUnits = [];
        $this->enemyInPersonalAreaForMyUnits[] = [];

        $this->defineUnitMap();
    }

    public function everyUnit(Unit $unit): void
    {
        $this->defineEnemyTargetForMyUnit($unit);
        $this->defineEnemyInPersonalAreaForMyUnit($unit);
    }

    private function defineUnitMap(): void
    {
        $this->myUnits = [];
        $this->enemies = [];

        foreach ($this->units as $unit) {
            if (!is_null($this->debugInterface)) {
                $this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 1, $unit->position->y - 2), "ID = " . $unit->id, new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
            }

            if ($unit->playerId == $this->myPlayerId) {
                $this->myUnits[$unit->id] = $unit;
            } else {
                $this->enemies[$unit->id] = $unit;
                $this->historyEnemies[$unit->id] = new MyHistoryUnit($unit, $this->currentTick);
            }
        }

        foreach ($this->historyEnemies as $enemyId => $historyEnemy) {
            if ($historyEnemy->tick < $this->currentTick - MyCommonConst::CNT_TICK_SAVE_HISTORY_ENEMY) {
                unset($this->historyEnemies[$enemyId]);
            } else {
                if (!isset($this->enemies[$enemyId]) && !is_null($this->debugInterface)) {
                    $this->debugInterface->add(new PlacedText(new Vec2($historyEnemy->unit->position->x - 1, $historyEnemy->unit->position->y), "ID = " . $historyEnemy->unit->id, new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                }
                if (!isset($this->enemies[$enemyId]) && !is_null($this->debugInterface)) {
                    $this->debugInterface->add(new Circle($historyEnemy->unit->position, $this->unitRadius + 1, MyColor::getColor(MyColor::VIOLET_05)));
                } //Рисуем примерную позицию звука
            }
        }
    }

    private function defineEnemyTargetForMyUnit(Unit $unit): void
    {
        $nearEnemy = null;
        $distanceToNearEnemy = null;

        foreach ($this->enemies as $enemy) {
            $distanceFromUnitToEnemy = Helper::getDistance($unit->position, $enemy->position);
            if (is_null($distanceToNearEnemy) || $distanceToNearEnemy > $distanceFromUnitToEnemy) {
                $distanceToNearEnemy = $distanceFromUnitToEnemy;
                $nearEnemy = $enemy;
            }
        }

        $this->targetEnemyForMyUnits[$unit->id] = $nearEnemy;
        if (!is_null($this->debugInterface) && !is_null($this->targetEnemyForMyUnits[$unit->id])) {
            $this->debugInterface->add(new Circle($this->targetEnemyForMyUnits[$unit->id]->position, $this->unitRadius + 1, MyColor::getColor(MyColor::RED_05)));
        }
    }

    private function defineEnemyInPersonalAreaForMyUnit(Unit $unit): void
    {
        foreach ($this->historyEnemies as $historyEnemy) {
            if (Helper::isPointInCircle($unit->position, $this->unitRadius * MyCommonConst::COEFFICIENT_PERSONAL_AREA, $historyEnemy->unit->position)) {
                $this->enemyInPersonalAreaForMyUnits[$unit->id][$historyEnemy->unit->id] = $historyEnemy->unit;
            }
        }
        foreach ($this->enemies as $enemy) {
            if (Helper::isPointInCircle($unit->position, $this->unitRadius * MyCommonConst::COEFFICIENT_PERSONAL_AREA, $enemy->position)) {
                $this->enemyInPersonalAreaForMyUnits[$unit->id][$enemy->id] = $enemy;
            }
        }
    }
}