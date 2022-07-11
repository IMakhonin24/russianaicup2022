<?php

use Debugging\Color;
use Debugging\DebugData;
use Debugging\DebugData\Circle;
use Debugging\DebugData\PlacedText;
use Debugging\DebugData\PolyLine;
use Model\ActionOrder;
use Model\Constants;
use Model\Game;
use Model\Item\Ammo;
use Model\Item\ShieldPotions;
use Model\Item\Weapon;
use Model\Loot;
use Model\Order;
use Model\Unit;
use Model\UnitOrder;
use Model\Vec2;

require_once 'Model/Game.php';
require_once 'Model/Order.php';
require_once 'Model/Constants.php';

class MyStrategy
{
    private Constants $constants;

    /**
     * @var array | Unit[]
     */
    private array $myUnits;
    /**
     * @var array | Unit[]
     */
    private array $enemies;

    private array $targetEnemyForMyUnits;

    /**
     * @var array | Loot[]
     */
    private array $visibleWeapon;
    /**
     * @var array | Loot[]
     */
    private array $visiblePot;
    /**
     * @var array | Loot[]
     */
    private array $visibleAmmo;

//    private array $projectile

    private bool $isDebugActive = true;


    private array $debugData = [];

    function __construct(Constants $constants)
    {
        $this->constants = $constants;
    }

    private function init(Game $game): void
    {
        $this->defineUnitMap($game);
        $this->defineLootMap($game);

        $this->defineEnemyTargetForMyUnit();
    }

    private function defineUnitMap(Game $game): void
    {
        $this->myUnits = [];
        $this->enemies = [];

        /** @var Unit[] $units */
        $units = $game->units;
        foreach ($units as $unit) {
            if ($unit->playerId == $game->myId) {
                $this->myUnits[$unit->id] = $unit;
            } else {
                $this->enemies[$unit->id] = $unit;
            }
        }
    }

    private function defineLootMap(Game $game): void
    {
        $this->visibleWeapon = [];
        $this->visiblePot = [];
        $this->visibleAmmo = [];

        /** @var Loot[] $loots */
        $loots = $game->loot;
        foreach ($loots as $loot) {
            $lootClass = get_class($loot->item);

            switch ($lootClass) {
                case Weapon::class:
                    $this->visibleWeapon[] = $loot;
                    break;
                case ShieldPotions::class:
                    $this->visiblePot[] = $loot;
                    break;
                case Ammo::class:
                    $this->visibleAmmo[] = $loot;
                    break;
            }
        }
    }

    private function defineEnemyTargetForMyUnit(): void
    {
        foreach ($this->myUnits as $myUnit) {

            $nearEnemy = null;
            $distanceToNearEnemy = null;

            foreach ($this->enemies as $enemy) {
                $distanceFromUnitToEnemy = Helper::getDistance($myUnit->position, $enemy->position);
                if (is_null($distanceToNearEnemy) || $distanceToNearEnemy > $distanceFromUnitToEnemy) {
                    $nearEnemy = $enemy;
                }
            }

            $this->targetEnemyForMyUnits[$myUnit->id] = $nearEnemy;
        }
    }

    function getOrder(Game $game, ?DebugInterface $debugInterface): Order
    {
        $this->init($game);
        $order = [];

        if (!!$this->isDebugActive) {
            $debugInterface->add(new Circle(
                $game->zone->currentCenter,
                $game->zone->currentRadius - 25,
                new Color(0, 0, 255, 0.1)
            ));
        }

        foreach ($this->myUnits as $unit) {

            $debugInterface->add(new PolyLine([new Vec2($unit->position->x, $unit->position->y), new Vec2($game->zone->currentCenter->x, $game->zone->currentCenter->y)], 0.1, new Color(255, 255, 255, 1)));

//            $this->debugData[$unit->id] = $unit->weapon;

            $order[$unit->id] = new UnitOrder(
                $this->footController($game, $unit),
                $this->eyeController($game, $unit, $debugInterface),
                $this->actionController()
            );
        }

        return new Order($order);
    }

    private function footController(Game $game, Unit $unit): Vec2
    {
        //увороты
//        $game->projectiles


//        return new Vec2($unit->velocity->x - $unit->position->x, $unit->velocity->y - $unit->position->y);
        return new Vec2($unit->velocity->x, $unit->velocity->y); //Стоим на месте;
    }

    private function eyeController(Game $game, Unit $unit, ?DebugInterface $debugInterface): Vec2
    {
        //осмотреться вправо вогруг каждые 5 сек
        if (($game->currentTick / $this->constants->ticksPerSecond) % 10 == 0) {
            return new Vec2($unit->direction->y, -$unit->direction->x);
        }

        if (isset($this->targetEnemyForMyUnits[$unit->id])) {
            $targetForMyUnit = $this->targetEnemyForMyUnits[$unit->id];
            if (!!$this->isDebugActive) {
                $debugInterface->add(new PolyLine([new Vec2($unit->position->x, $unit->position->y), new Vec2($targetForMyUnit->position->x, $targetForMyUnit->position->y)], 0.1, new Color(0, 0, 255, 1)));
            }
            return Helper::getVectorAB($unit->position, $targetForMyUnit->position);
        }


        //взгляд Vec2(1,0) - право,       Vec2(1,0) - вверх,

        //осмотреться вправо вогруг;
//        $result = new Vec2($unit->direction->y,  -$unit->direction->x);
        $result = new Vec2(1, 1);

        return $result;
    }

    private function actionController(): ActionOrder
    {
        return new Model\ActionOrder\Aim(true);
    }


    function debugUpdate(DebugInterface $debug_interface)
    {

//Не вывелось
//            $debugInterface->add(new PlacedText(
//                new Vec2($unit->position->x, $unit->position->y),
//                "QQQQQQQQQQQQQWWWWWWWWWWWWWWW",
//                new Vec2($unit->position->x, $unit->position->y),
//                20,
//                new Color(255,0,0, 1)
//            ));


//Нарисовать линию. Работает
//            $debugInterface->add(new PolyLine(
//                [
//                    new Vec2($unit->position->x, $unit->position->y),
//                    new Vec2($unit->position->x+10, $unit->position->y+10),
//                ], 1, new Color(255,0,0, 1)
//            ));


    }

    function finish()
    {
    }
}

class Helper
{
    public static function getDistance(Vec2 $a, Vec2 $b): float
    {
        return ($a->x - $b->x) * ($a->x - $b->x) + ($a->y - $b->y) * ($a->y - $b->y);
    }

    public static function getVectorAB(Vec2 $a, Vec2 $b): Vec2
    {
        return new Vec2($b->x - $a->x, $b->y - $a->y);
    }

    public static function isPointInCircle(Vec2 $centreCircle, float $radius, Vec2 $checkPoint): bool
    {
        $hypotenuse = sqrt(($checkPoint->x * $checkPoint->x) + ($checkPoint->y * $checkPoint->y));
        if ($hypotenuse <= $radius) {
            return true;
        }
        return false;
    }
}