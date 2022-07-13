<?php

use Debugging\Color;
use Debugging\DebugData\Circle;
use Debugging\DebugData\PolyLine;
use Model\ActionOrder;
use Model\ActionOrder\Aim;
use Model\ActionOrder\Pickup;
use Model\ActionOrder\UseShieldPotion;
use Model\Constants;
use Model\Game;
use Model\Item\Ammo;
use Model\Item\ShieldPotions;
use Model\Item\Weapon;
use Model\Loot;
use Model\Order;
use Model\Projectile;
use Model\Unit;
use Model\UnitOrder;
use Model\Vec2;

require_once 'Model/Game.php';
require_once 'Model/Order.php';
require_once 'Model/Constants.php';

class MyStrategy
{
    private Constants $constants;
    private Game $game;

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
    private array $historyPot;
    private array $nearestPotForMyUnits;
    /**
     * @var Loot
     */
    private array $nearestPot;
    /**
     * @var array | Loot[]
     */
    private array $visibleAmmo;
    /**
     * @var array | Projectile[]
     */
    private array $projectiles;

    private bool $isDebugActive = false;
    private ?DebugInterface $debugInterface;


    function __construct(Constants $constants)
    {
        $this->constants = $constants;
    }

    private function init(Game $game): void
    {
        $this->defineUnitMap($game);
        $this->defineLootMap($game);
        $this->defineProjectilesMap($game);
    }

    private function initForUnit(Unit $unit): void
    {
        $this->defineEnemyTargetForMyUnit($unit);
        $this->defineNearestPotMyUnit($unit);
    }

    function getOrder(Game $game, ?DebugInterface $debugInterface): Order
    {
        $this->debugInterface = $debugInterface;
        $this->game = $game;
        $this->init($game);
        $order = [];

        if (!is_null($this->debugInterface)){
            $this->debugInterface->add(new Circle(
                $game->zone->currentCenter,
                $game->zone->currentRadius - 25,
                new Color(0, 0, 255, 0.1)
            ));
        }


        foreach ($this->myUnits as $unit) {
            $this->initForUnit($unit);
            $order[$unit->id] = new UnitOrder(
                $this->footController($game, $unit),
                $this->eyeController($game, $unit),
                $this->actionController($game, $unit)
            );
        }

        return new Order($order);
    }

    private function footController(Game $game, Unit $unit): Vec2
    {
        //увороты
        $ur11 = 0;
        $ur12 = 0;
        $rd11 = 0;
        $rd12 = 0;
        $dl11 = 0;
        $dl12 = 0;
        $lu11 = 0;
        $lu12 = 0;

        $userFictiveRadius = $this->constants->unitRadius + 2;
        if (!is_null($this->debugInterface)){
            $this->debugInterface->add(new Circle(
                $unit->position,
                $userFictiveRadius,
                new Color(0, 255, 0, 0.2)
            ));
        }

        foreach ($this->projectiles as $projectile) {
            if (Helper::isIntersectionLineAndCircle($projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y), $unit->position, $userFictiveRadius)) {
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, new Color(0, 0, 0, 0.2)));}

                $lineA = $projectile->position;
                $lineB = new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y);
                $perpendC = new Vec2($lineB->x - $lineA->x, $lineB->y - $lineA->y);
                $perpendD = Helper::getPerpendicularTo($lineA, $lineB, $perpendC);
                $pointZero = new Vec2(0, 0);

                $perpendLineFromZero = new Vec2($perpendD->x - $perpendC->x, $perpendD->y - $perpendC->y);

                if (Helper::isIntersectionTwoLine($pointZero, $perpendLineFromZero, new Vec2(0, 10), new Vec2(10, 10))) {
                    $ur11++;
                }
                if (Helper::isIntersectionTwoLine($pointZero, $perpendLineFromZero, new Vec2(10, 10), new Vec2(10, 0))) {
                    $ur12++;
                }
                if (Helper::isIntersectionTwoLine($pointZero, $perpendLineFromZero, new Vec2(10, 0), new Vec2(10, -10))) {
                    $rd11++;
                }
                if (Helper::isIntersectionTwoLine($pointZero, $perpendLineFromZero, new Vec2(10, -10), new Vec2(0, -10))) {
                    $rd12++;
                }
                if (Helper::isIntersectionTwoLine($pointZero, $perpendLineFromZero, new Vec2(0, -10), new Vec2(-10, -10))) {
                    $dl11++;
                }
                if (Helper::isIntersectionTwoLine($pointZero, $perpendLineFromZero, new Vec2(-10, -10), new Vec2(-10, 0))) {
                    $dl12++;
                }
                if (Helper::isIntersectionTwoLine($pointZero, $perpendLineFromZero, new Vec2(-10, 0), new Vec2(-10, 10))) {
                    $lu11++;
                }
                if (Helper::isIntersectionTwoLine($pointZero, $perpendLineFromZero, new Vec2(-10, 10), new Vec2(0, 10))) {
                    $lu12++;
                }
            } else {
                //просто рисуем все пули
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, new Color(255, 255, 255, 0.1)));}
            }
        }

        $saveSectors = [];
        if ($ur11 > 0) {
            $saveSectors['UR1'] = $ur11;
        }
        if ($ur12 > 0) {
            $saveSectors['UR2'] = $ur12;
        }
        if ($rd11 > 0) {
            $saveSectors['RD1'] = $rd11;
        }
        if ($rd12 > 0) {
            $saveSectors['RD2'] = $rd12;
        }
        if ($dl11 > 0) {
            $saveSectors['DL1'] = $dl11;
        }
        if ($dl12 > 0) {
            $saveSectors['DL2'] = $dl12;
        }
        if ($lu11 > 0) {
            $saveSectors['LU1'] = $lu11;
        }
        if ($lu12 > 0) {
            $saveSectors['LU2'] = $lu12;
        }
        krsort($saveSectors);
        $priorityDirection = array_key_first($saveSectors);
        if ($priorityDirection == "UR1" || $priorityDirection == "DL1") {
            $nextUnitPosition1 = new Vec2($unit->position->x + 4, $unit->position->y + 10);
            $nextUnitPosition2 = new Vec2($unit->position->x - 4, $unit->position->y - 10);
        } elseif ($priorityDirection == "UR2" || $priorityDirection == "DL2") {
            $nextUnitPosition1 = new Vec2($unit->position->x + 10, $unit->position->y + 4);
            $nextUnitPosition2 = new Vec2($unit->position->x - 10, $unit->position->y - 4);
        } elseif ($priorityDirection == "RD1" || $priorityDirection == "LU1") {
            $nextUnitPosition1 = new Vec2($unit->position->x + 10, $unit->position->y - 4);
            $nextUnitPosition2 = new Vec2($unit->position->x - 10, $unit->position->y + 4);
        } elseif ($priorityDirection == "RD2" || $priorityDirection == "LU2") {
            $nextUnitPosition1 = new Vec2($unit->position->x + 4, $unit->position->y - 10);
            $nextUnitPosition2 = new Vec2($unit->position->x - 4, $unit->position->y + 10);
        }

        if (isset($nextUnitPosition1) && isset($nextUnitPosition2)) {
            $numberHit1 = 0;
            $numberHit2 = 0;
            foreach ($this->projectiles as $projectile) {
                if (Helper::isIntersectionLineAndCircle($projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y), $nextUnitPosition1, $this->constants->unitRadius)) {
                    $numberHit1++;
                }
                if (Helper::isIntersectionLineAndCircle($projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y), $nextUnitPosition2, $this->constants->unitRadius)) {
                    $numberHit2++;
                }
            }
            $position = ($numberHit1 >= $numberHit2) ? $nextUnitPosition2 : $nextUnitPosition1;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $position], 0.1, new Color(0, 0, 255, 0.5)));}
            return $this->goToPosition($unit, $position);
        }

        //уходим от зоны
        if (!Helper::isPointInCircle($game->zone->currentCenter, $game->zone->currentRadius - 25, $unit->position)) {
            return $this->goToPosition($unit, new Vec2(0, 0));
        }

        //Идем искать зелья
        if ($unit->shieldPotions < $this->constants->maxShieldPotionsInInventory && isset($this->nearestPotForMyUnits[$unit->id])) {
            $pot = $this->nearestPotForMyUnits[$unit->id];
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $pot->position], 0.1, new Color(0, 0, 255, 0.5)));}
            return $this->goToPosition($unit, $pot->position);
        }

        return $this->goToPosition($unit, $unit->position); //Стоим на месте;
    }

    private function eyeController(Game $game, Unit $unit): Vec2
    {
        //осмотреться вправо вогруг каждые 5 сек
        if (($game->currentTick / $this->constants->ticksPerSecond) % 10 == 0) {
            $targetPosition = new Vec2($unit->direction->y, -$unit->direction->x);
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $targetPosition], 0.1, new Color(0, 255, 0, 0.5)));}
            return $targetPosition;
        }

        if (isset($this->targetEnemyForMyUnits[$unit->id])) {
            $targetForMyUnit = $this->targetEnemyForMyUnits[$unit->id];
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $targetForMyUnit->position], 0.1, new Color(0, 255, 0, 0.5)));}
            return Helper::getVectorAB($unit->position, $targetForMyUnit->position);
        }
        return new Vec2($unit->direction->y, -$unit->direction->x);
    }

    private function actionController(Game $game, Unit $unit): ActionOrder
    {
        //если есть зелья и применяемое зелье не перекроет максимум то применяем зелье щита
        if ($unit->shieldPotions > 0 && $unit->shield + $this->constants->shieldPerPotion < $this->constants->maxShield) {
            if ($this->isDebugActive) {
                var_dump('Применяю пот');
            }
            return new UseShieldPotion();
        }

        //Подбираем пот
        if ($unit->shieldPotions < $this->constants->maxShieldPotionsInInventory && isset($this->nearestPotForMyUnits[$unit->id])) {
            $pot = $this->nearestPotForMyUnits[$unit->id];
            $distanceFromUnitToPot = Helper::getDistance($unit->position, $pot->position);
            if ($distanceFromUnitToPot < $this->constants->unitRadius) {
                $this->deleteFromHistoryLoot($pot);
                if ($this->isDebugActive) {
                    var_dump('Подбираем пот ' . $unit->shieldPotions . ' / ' . $this->constants->maxShieldPotionsInInventory);
                }
                return new Pickup($pot->id);
            }
        }
        if (isset($this->targetEnemyForMyUnits[$unit->id])) {
            return new Aim(true);
        }

        return new Aim(false);
    }


    public function goToPosition(Unit $unit, Vec2 $targetPosition): Vec2
    {
        if (Helper::isPointInCircle($unit->position, $this->constants->unitRadius, $targetPosition)) {
            return new Vec2(0, 0);
        } else {
            return Helper::getVectorAB($unit->position, $targetPosition);
        }
    }

    function debugUpdate(DebugInterface $debug_interface)
    {
    }

    function finish()
    {
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
                    $this->historyPot[$loot->id] = $loot;
                    break;
                case Ammo::class:
                    $this->visibleAmmo[] = $loot;
                    break;
            }
        }
    }

    private function defineProjectilesMap(Game $game): void
    {
        $this->projectiles = [];

        /** @var Projectile[] $projectiles */
        $projectiles = $game->projectiles;
        foreach ($projectiles as $projectile) {
            if ($projectile->shooterPlayerId != $game->myId) {
                $this->projectiles[$projectile->id] = $projectile;
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
    }

    private function defineNearestPotMyUnit(Unit $unit): void
    {
        $nearPot = null;
        $distanceToNearPot = null;

        foreach ($this->historyPot as $pot) {
            $distanceFromUnitToPot = Helper::getDistance($unit->position, $pot->position);
            $isPotInGreyZone = Helper::isPointInCircle($this->game->zone->currentCenter, $this->game->zone->currentRadius - 25, $unit->position);
            if (!$isPotInGreyZone) {
                continue;
            }

            if (is_null($distanceToNearPot) || $distanceToNearPot > $distanceFromUnitToPot) {
                $distanceToNearPot = $distanceFromUnitToPot;
                $nearPot = $pot;
            }
        }

        $this->nearestPotForMyUnits[$unit->id] = $nearPot;
    }


    private function deleteFromHistoryLoot(Loot $loot): void
    {
        if (isset($this->historyPot[$loot->id])) {
            unset($this->historyPot[$loot->id]);
        }
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
        $x = $checkPoint->x - $centreCircle->x;
        $y = $checkPoint->y - $centreCircle->y;
        $hypotenuse = sqrt(($x * $x) + ($y * $y));
        if ($hypotenuse <= $radius) {
            return true;
        }
        return false;
    }

    public static function isIntersectionLineAndCircle(Vec2 $x1, Vec2 $x2, Vec2 $circleCentre, float $radius): bool
    {
        $dx01 = $x1->x - $circleCentre->x;
        $dy01 = $x1->y - $circleCentre->y;
        $dx12 = $x2->x - $x1->x;
        $dy12 = $x2->y - $x1->y;

        $a = pow($dx12, 2) + pow($dy12, 2);

        if (abs($a) < PHP_FLOAT_EPSILON) {
            return false;//Начало и конец отрезка совпадают
        }

        $k = $dx01 * $dx12 + $dy01 * $dy12;
        $c = pow($dx01, 2) + pow($dy01, 2) - pow($radius, 2);
        $d1 = pow($k, 2) - $a * $c;

        if ($d1 < 0) {
            return false; //Отрезок не пересекается с окружностью - отрезок снаружи
        } else if (abs($d1) < PHP_FLOAT_EPSILON) {
            return false; //Прямая касается окружности в точке
        } else {
            return true; //Прямая пересекает окружность в двух точках
        }
    }

    public static function isIntersectionTwoLine(Vec2 $pointA, Vec2 $pointB, Vec2 $pointC, Vec2 $pointD): bool
    {
        $v1 = ($pointD->x - $pointC->x) * ($pointA->y - $pointC->y) - ($pointD->y - $pointC->y) * ($pointA->x - $pointC->x);
        $v2 = ($pointD->x - $pointC->x) * ($pointB->y - $pointC->y) - ($pointD->y - $pointC->y) * ($pointB->x - $pointC->x);
        $v3 = ($pointB->x - $pointA->x) * ($pointC->y - $pointA->y) - ($pointB->y - $pointA->y) * ($pointC->x - $pointA->x);
        $v4 = ($pointB->x - $pointA->x) * ($pointD->y - $pointA->y) - ($pointB->y - $pointA->y) * ($pointD->x - $pointA->x);

        if (($v1 * $v2 < 0) && ($v3 * $v4 < 0)) {
            return true;
        }
        return false;
    }

    public static function getPerpendicularTo(Vec2 $lineA, Vec2 $lineB, Vec2 $pointC): Vec2
    {
        $x1 = $lineA->x;
        $y1 = $lineA->y;
        $x2 = $lineB->x;
        $y2 = $lineB->y;
        $x3 = $pointC->x;
        $y3 = $pointC->y;

        $x = ($x1 * $x1 * $x3 - 2 * $x1 * $x2 * $x3 + $x2 * $x2 * $x3 + $x2 * ($y1 - $y2) * ($y1 - $y3) - $x1 * ($y1 - $y2) * ($y2 - $y3)) / (($x1 - $x2) * ($x1 - $x2) + ($y1 - $y2) * ($y1 - $y2));
        $y = ($x2 * $x2 * $y1 + $x1 * $x1 * $y2 + $x2 * $x3 * ($y2 - $y1) - $x1 * ($x3 * ($y2 - $y1) + $x2 * ($y1 + $y2)) + ($y1 - $y2) * ($y1 - $y2) * $y3) / (($x1 - $x2) * ($x1 - $x2) + ($y1 - $y2) * ($y1 - $y2));

        return new Vec2($x, $y);

    }
}
