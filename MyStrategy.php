<?php

use Debugging\Color;
use Debugging\DebugData\Circle;
use Debugging\DebugData\PlacedText;
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
    private MyColor $MC;

    /**
     * @var array | Unit[]
     */
    private array $myUnits;

    /**
     * @var array | Unit[]
     */
    private array $enemies;

    /**
     * @var array | Unit[]
     */
    private array $targetEnemyForMyUnits;

    /**
     * @var array | Loot[][]
     */
    private array $visibleWeapon;
    /**
     * @var array | Loot[][]
     */
    private array $historyWeapon = [];
    /**
     * @var array | Loot[]
     */
    private array $nearestWeaponForMyUnits;

    /**
     * @var array | Loot[]
     */
    private array $visiblePot;
    /**
     * @var array | Loot[]
     */
    private array $historyPot = [];
    /**
     * @var array | Loot[]
     */
    private array $nearestPotForMyUnits;

    /**
     * @var array | Loot[][]
     */
    private array $visibleAmmo;
    /**
     * @var array | Loot[][]
     */
    private array $historyAmmo = [];
    /**
     * @var array | Loot[]
     */
    private array $nearestAmmoForMyUnits;

    /**
     * @var array | Projectile[]
     */
    private array $projectiles; //Массив пуль

    /**
     * @var Vec2[]
     */
    private array $actionForMyUnitsFoot; //Массив по юнитам. Точка Vec2 куда идет. Именно targetPosition a не вектор ускорения
    /**
     * @var Vec2[]
     */
    private array $actionForMyUnitsEye; //Массив по юнитам. Точка Vec2 куда смотрит.
    /**
     * @var ActionOrder[]
     */
    private array $actionForMyUnitsAction; //Массив по юнитам. Объект действия.

    private array $actionTypeForMyUnitsFoot;    //Массив  по юнитам. Тип действия ног. для координации
    private array $actionTypeForMyUnitsEye;    //Массив  по юнитам. Тип действия глаз. для координации
    private array $actionTypeForMyUnitsAction;    //Массив  по юнитам. Тип действия рук. для координации

    private bool $isDebugActive = true;
    private ?DebugInterface $debugInterface;

    function __construct(Constants $constants)
    {
        $this->constants = $constants;
        $this->MC = new MyColor();
    }

    private function init(Game $game): void
    {
        $this->targetEnemyForMyUnits = [];
        $this->nearestWeaponForMyUnits = [];
        $this->nearestPotForMyUnits = [];
        $this->nearestAmmoForMyUnits = [];

        $this->defineHistoryDebug();

        $this->defineUnitMap($game);
        $this->defineLootMap($game);
        $this->defineProjectilesMap($game);
    }

    private function initForUnit(Unit $unit): void
    {
        $this->actionForMyUnitsFoot = [];
        $this->actionForMyUnitsEye = [];
        $this->actionForMyUnitsAction = [];
        $this->actionTypeForMyUnitsFoot = [];
        $this->actionTypeForMyUnitsEye = [];
        $this->actionTypeForMyUnitsAction = [];

        $this->defineEnemyTargetForMyUnit($unit);
        $this->defineNearestWeaponForMyUnit($unit);
        $this->defineNearestPotForMyUnit($unit);
        $this->defineNearestAmmoForMyUnit($unit);
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
                $this->MC->lightGreen01
            ));
        }

        foreach ($this->myUnits as $unit) {
            $this->initForUnit($unit);

            $order[$unit->id] = new UnitOrder(
                $this->footController($game, $unit),
                $this->eyeController($game, $unit),
                $this->actionController($game, $unit)
            );


            //Вывожу параметры юнитов
            foreach ($unit->ammo as $ammoType => $ammoCnt) {
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y + 2 + ($ammoType * -0.3)), "Ammo[".$ammoType."] = ".$ammoCnt, new Vec2(0, 0), 0.3, $this->MC->blue1));}
            }
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y + 1), "Weapon: " . MyWeapon::getName($unit->weapon), new Vec2(0, 0), 0.3, $this->MC->blue1));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y+0.6), "Pots: " . $unit->shieldPotions . "/" .$this->constants->maxShieldPotionsInInventory, new Vec2(0, 0), 0.3, $this->MC->blue1));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y-1.3), "DistanceToTargetEnemy: ".(isset($this->targetEnemyForMyUnits[$unit->id])?Helper::getDistance($unit->position, $this->targetEnemyForMyUnits[$unit->id]->position) : 'null'), new Vec2(0, 0), 0.3, $this->MC->blue1));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y-1.6), "Velocity: (". (int)$unit->velocity->x.";".(int)$unit->velocity->y.") = ".(int)Helper::getDistance($unit->position,new Vec2($unit->position->x + $unit->velocity->x,$unit->position->y + $unit->velocity->y)), new Vec2(0, 0), 0.3, $this->MC->blue1));}
        }

        return new Order($order);
    }

    private function footController(Game $game, Unit $unit): Vec2
    {
        //===================Увороты========================
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
                $this->MC->green02
            ));
        }

        foreach ($this->projectiles as $projectile) {
            if (Helper::isIntersectionLineAndCircle($projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y), $unit->position, $userFictiveRadius)) {
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, $this->MC->black02));}

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
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, $this->MC->white01));}
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
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::TRY_TO_MISS_BULLET;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $position], 0.1, $this->MC->blue05));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Увороты", new Vec2(0, 0), 0.5, $this->MC->blue1));}
            return $this->goToPosition($unit, $position);
        }
        //===================Увороты========================


        //===================Уходим от зоны========================
        if (!Helper::isPointInCircle($game->zone->currentCenter, $game->zone->currentRadius - 25, $unit->position)) {
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::GO_OUT_FROM_GREY_ZONE;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Ухожу из серой зоны", new Vec2(0, 0), 0.5, $this->MC->blue1));}
            return $this->goToPosition($unit, new Vec2(0, 0));
        }
        //===================Уходим от зоны========================

        //===================Ищем снайперку========================
        if ($unit->weapon < MyWeapon::SNIPER && $unit->ammo[MyWeapon::SNIPER] > 0 && isset($this->nearestWeaponForMyUnits[$unit->id][MyWeapon::SNIPER])){
            $weaponSniper = $this->nearestWeaponForMyUnits[$unit->id][MyWeapon::SNIPER];
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::GO_TO_WEAPON_SNIPER;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $weaponSniper->position], 0.1, $this->MC->blue05));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Иду к ближ. снайп. оруж.", new Vec2(0, 0), 0.5, $this->MC->blue1));}
            return $this->goToPosition($unit, $weaponSniper->position);
        }
        //===================Ищем снайперку========================

        //===================Идем брать ближайшие патроны для снайперки========================
        if ($unit->ammo[MyWeapon::SNIPER] < 10 && isset($this->nearestAmmoForMyUnits[$unit->id][MyWeapon::SNIPER])){
            $weaponAmmoSniper = $this->nearestAmmoForMyUnits[$unit->id][MyWeapon::SNIPER];
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::GO_TO_AMMO_SNIPER;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $weaponAmmoSniper->position], 0.1, $this->MC->blue05));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Иду к ближ. патрон. cнайп. оруж.", new Vec2(0, 0), 0.5, $this->MC->blue1));}
            return $this->goToPosition($unit, $weaponAmmoSniper->position);
        }
        //===================Идем брать ближайшие патроны для снайперки========================

        //===================Идем искать зелья========================
        if ($unit->shieldPotions < $this->constants->maxShieldPotionsInInventory && isset($this->nearestPotForMyUnits[$unit->id])) {
            $pot = $this->nearestPotForMyUnits[$unit->id];
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::GO_TO_POT;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $pot->position], 0.1, $this->MC->blue05));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Иду к ближ. поту", new Vec2(0, 0), 0.5, $this->MC->blue1));}
            return $this->goToPosition($unit, $pot->position);
        }
        //===================Идем искать зелья========================

        $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::STAY;
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Стою на месте", new Vec2(0, 0), 0.5, $this->MC->blue1));}
        return $this->goToPosition($unit, $unit->position);
    }

    private function eyeController(Game $game, Unit $unit): Vec2
    {
        //===================Смотрю вперед когда уворачиваюсь от пуль========================
        if ((isset($this->actionTypeForMyUnitsFoot[$unit->id]) && $this->actionTypeForMyUnitsFoot[$unit->id] == MyAction::TRY_TO_MISS_BULLET) && isset($this->actionForMyUnitsFoot[$unit->id])){
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AT_UNIT_FORWARD_WHILE_MISS_BULLET;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю по ходу движения во вреия уворотов", new Vec2(0, 0), 0.5, $this->MC->red1));}
            return Helper::getVectorAB($unit->position, $this->actionForMyUnitsFoot[$unit->id]);
        }
        //===================Смотрю вперед когда уворачиваюсь от пуль========================

        //===================Осмотреться по таймеру========================
        if (($game->currentTick / $this->constants->ticksPerSecond) % 10 == 0) {
            $targetPosition = new Vec2($unit->direction->y, -$unit->direction->x);
            $this->actionForMyUnitsEye[$unit->id] = $targetPosition;
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AROUND_BY_TIMER;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Осмотреться по таймеру", new Vec2(0, 0), 0.5, $this->MC->red1));}
            return $targetPosition;
        }
        //===================Осмотреться по таймеру========================

        //===================Смотрю на target========================
        if (isset($this->targetEnemyForMyUnits[$unit->id])) {
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю на targetEnemy", new Vec2(0, 0), 0.5, $this->MC->red1));}
            $targetForMyUnit = $this->targetEnemyForMyUnits[$unit->id];
            //куда идет цель
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$targetForMyUnit->position, new Vec2($targetForMyUnit->position->x + $targetForMyUnit->velocity->x, $targetForMyUnit->position->y + $targetForMyUnit->velocity->y)], 0.1, $this->MC->red05));}
            $currentDistanceToTarget = Helper::getDistance($unit->position, $targetForMyUnit->position);
            $k = $currentDistanceToTarget < 100 ? 100 : ($currentDistanceToTarget < 200 ? 2 : (2));
            $targetForMyUnit = new Vec2($targetForMyUnit->position->x + ($targetForMyUnit->velocity->x / $k), $targetForMyUnit->position->y + ($targetForMyUnit->velocity->y / $k));
            $this->actionForMyUnitsEye[$unit->id] = $targetForMyUnit;
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AT_TARGET_ENEMY;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $targetForMyUnit], 0.1, $this->MC->red05));}
            return Helper::getVectorAB($unit->position, $targetForMyUnit);
        }
        //===================Cмотрю на target========================

        //===================Смотрю по ходу движения========================
        if (isset($this->actionForMyUnitsFoot[$unit->id])){
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AT_UNIT_FORWARD;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю по ходу движения", new Vec2(0, 0), 0.5, $this->MC->red1));}
            return Helper::getVectorAB($unit->position, $this->actionForMyUnitsFoot[$unit->id]);
        }
        //===================Смотрю по ходу движения========================

        //===================Смотрю по куругу========================
        $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AROUND;
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю по куругу", new Vec2(0, 0), 0.5, $this->MC->red1));}
        return new Vec2($unit->direction->y, -$unit->direction->x);
        //===================Смотрю по куругу========================
    }

    private function actionController(Game $game, Unit $unit): ActionOrder
    {
        //если есть зелья и применяемое зелье не перекроет максимум то применяем зелье щита
        if ($unit->shieldPotions > 0 && $unit->shield + $this->constants->shieldPerPotion < $this->constants->maxShield) {
            $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::TYPE_USE_POT;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Применяю пот", new Vec2(0, 0), 0.5, $this->MC->green1));}
            return new UseShieldPotion();
        }

        //Подбираем патроны для снайперки
        if (isset($this->nearestAmmoForMyUnits[$unit->id][MyWeapon::SNIPER])) {
            $weaponAmmoSniper = $this->nearestAmmoForMyUnits[$unit->id][MyWeapon::SNIPER];
            $distanceFromUnitToWeaponAmmo = Helper::getDistance($unit->position, $weaponAmmoSniper->position);
            if ($distanceFromUnitToWeaponAmmo < $this->constants->unitRadius) {
                $this->deleteFromHistoryAmmo($weaponAmmoSniper);
                $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::RAISE_AMMO_SNIPER;
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Подбираю патроны для снайперки", new Vec2(0, 0), 0.5, $this->MC->green1));}
                return new Pickup($weaponAmmoSniper->id);
            }
        }

        //Подбираем снайперку
        if (isset($this->nearestWeaponForMyUnits[$unit->id][MyWeapon::SNIPER])) {
            $weaponSniper = $this->nearestWeaponForMyUnits[$unit->id][MyWeapon::SNIPER];
            $distanceFromUnitTWeapon = Helper::getDistance($unit->position, $weaponSniper->position);
            if ($distanceFromUnitTWeapon < $this->constants->unitRadius) {
                $this->deleteFromHistoryWeapon($weaponSniper);
                $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::RAISE_WEAPON_SNIPER;
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Подбираю снайперку", new Vec2(0, 0), 0.5, $this->MC->green1));}
                return new Pickup($weaponSniper->id);
            }
        }

        //Подбираем пот
        if ($unit->shieldPotions < $this->constants->maxShieldPotionsInInventory && isset($this->nearestPotForMyUnits[$unit->id])) {
            $pot = $this->nearestPotForMyUnits[$unit->id];
            $distanceFromUnitToPot = Helper::getDistance($unit->position, $pot->position);
            if ($distanceFromUnitToPot < $this->constants->unitRadius) {
                $this->deleteFromHistoryPot($pot);
                $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::RAISE_POT;
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Подбираю пот", new Vec2(0, 0), 0.5, $this->MC->green1));}
                return new Pickup($pot->id);
            }
        }

        //Стреляем по таргет юниту
        if ((isset($this->targetEnemyForMyUnits[$unit->id]) && isset($this->actionForMyUnitsEye[$unit->id]) && $this->actionForMyUnitsEye[$unit->id] == MyAction::LOOK_AT_TARGET_ENEMY) && Helper::getDistance($unit->position, $this->targetEnemyForMyUnits[$unit->id]->position) < 500) {
            $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::SHOOT_TO_TARGET_ENEMY;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Стреляю в targetEnemy", new Vec2(0, 0), 0.5, $this->MC->green1));}
            return new Aim(true);
        }

        $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::DO_NOTHING;
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Ничего не делаю", new Vec2(0, 0), 0.5, $this->MC->green1));}
        return new Aim(false);
    }


    public function goToPosition(Unit $unit, Vec2 $targetPosition): Vec2
    {
        $this->actionForMyUnitsFoot[$unit->id] = $targetPosition;
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle($targetPosition, 0.5, $this->MC->blue05));}
        if (Helper::isPointInCircle($unit->position, $this->constants->unitRadius, $targetPosition)) {
            return new Vec2(0, 0);
        } else {
            $vec = Helper::getVectorAB($unit->position, $targetPosition);
            $vec = new Vec2($this->constants->maxUnitForwardSpeed * $vec->x, $this->constants->maxUnitForwardSpeed * $vec->y);//увеличение вектора, для скорости юнита
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([new Vec2($unit->position->x-0.5, $unit->position->y+0.5), new Vec2($unit->position->x+$vec->x-0.5, $unit->position->y+$vec->y+0.5)], 0.1, $this->MC->aqua01));}
            return Helper::getVectorAB($unit->position, new Vec2($unit->position->x + $vec->x, $unit->position->y + $vec->y) );
        }
    }

    function debugUpdate(DebugInterface $debug_interface)
    {
    }

    function finish()
    {
    }































    private function defineHistoryDebug(): void
    {
        foreach ($this->historyWeapon as $historyByWeaponType) {
            foreach ($historyByWeaponType as $loot) {
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle(new Vec2($loot->position->x, $loot->position->y+0.5), 0.2, $this->MC->black1));}
            }
        }
        foreach ($this->historyPot as $loot) {
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle(new Vec2($loot->position->x, $loot->position->y+0.5), 0.2, $this->MC->black1));}
        }
        foreach ($this->historyAmmo as $historyByWeaponAmmoType) {
            foreach ($historyByWeaponAmmoType as $loot) {
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle(new Vec2($loot->position->x, $loot->position->y+0.5), 0.2, $this->MC->black1));}
            }
        }
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
        //todo удалять hitorypot если его нет.
        //TODO ОСМОТРЕТЬСЯ ЕСЛИ РЯДОМ БЫЛ ЗВУК
        //держать дистанцию

        $this->visibleWeapon = [];
        $this->visiblePot = [];
        $this->visibleAmmo = [];

        /** @var Loot[] $loots */
        $loots = $game->loot;
        foreach ($loots as $loot) {
            $lootClass = get_class($loot->item);
            switch ($lootClass) {
                case Weapon::class:
                    /** @var Weapon $weapon */
                    $weapon = $loot->item;
                    $this->visibleWeapon[$weapon->typeIndex][] = $loot;
                    $this->historyWeapon[$weapon->typeIndex][$loot->id] = $loot;
                    if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle(new Vec2($loot->position->x, $loot->position->y+0.5), 0.1, $this->MC->aqua1));}
                    break;
                case ShieldPotions::class:
                    $this->visiblePot[] = $loot;
                    $this->historyPot[$loot->id] = $loot;
                    if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle(new Vec2($loot->position->x, $loot->position->y+0.5), 0.1, $this->MC->aqua1));}
                    break;
                case Ammo::class:
                    /** @var Ammo $ammo */
                    $ammo = $loot->item;
                    $this->visibleAmmo[$ammo->weaponTypeIndex][] = $loot;
                    $this->historyAmmo[$ammo->weaponTypeIndex][$loot->id] = $loot;
                    if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle(new Vec2($loot->position->x, $loot->position->y+0.5), 0.1, $this->MC->aqua1));}
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
        if (!is_null($this->debugInterface) && !is_null($this->targetEnemyForMyUnits[$unit->id])){$this->debugInterface->add(new Circle($this->targetEnemyForMyUnits[$unit->id]->position, $this->constants->unitRadius + 1, $this->MC->red05));}
    }

    private function defineNearestWeaponForMyUnit(Unit $unit): void
    {
        foreach ($this->historyWeapon as $weaponTypeIndex => $weapons) {
            $nearWeapon = null;
            $distanceToNearWeapon = null;
            foreach ($weapons as $weapon) {
                $distanceFromUnitToWeapon = Helper::getDistance($unit->position, $weapon->position);
                $isWeaponInGreenZone = Helper::isPointInCircle($this->game->zone->currentCenter, $this->game->zone->currentRadius - 25, $weapon->position);
                if (!$isWeaponInGreenZone) {
                    continue;
                }
                if (is_null($nearWeapon) || $distanceToNearWeapon > $distanceFromUnitToWeapon) {
                    $distanceToNearWeapon = $distanceFromUnitToWeapon;
                    $nearWeapon = $weapon;
                }
            }
            $this->nearestWeaponForMyUnits[$unit->id][$weaponTypeIndex] = $nearWeapon;
        }
    }

    private function defineNearestPotForMyUnit(Unit $unit): void
    {
        $nearPot = null;
        $distanceToNearPot = null;

        foreach ($this->historyPot as $pot) {
            $distanceFromUnitToPot = Helper::getDistance($unit->position, $pot->position);
            $isPotInGreenZone = Helper::isPointInCircle($this->game->zone->currentCenter, $this->game->zone->currentRadius - 25, $pot->position);
            if (!$isPotInGreenZone) {
                continue;
            }

            if (is_null($distanceToNearPot) || $distanceToNearPot > $distanceFromUnitToPot) {
                $distanceToNearPot = $distanceFromUnitToPot;
                $nearPot = $pot;
            }
        }

        $this->nearestPotForMyUnits[$unit->id] = $nearPot;
    }

    private function defineNearestAmmoForMyUnit(Unit $unit): void
    {
        foreach ($this->historyAmmo as $weaponTypeIndex => $ammos) {
            $nearAmmo = null;
            $distanceToNearAmmo = null;

            foreach ($ammos as $ammo) {
                $distanceFromUnitToAmmo = Helper::getDistance($unit->position, $ammo->position);
                $isAmmoInGreenZone = Helper::isPointInCircle($this->game->zone->currentCenter, $this->game->zone->currentRadius - 25, $ammo->position);
                if (!$isAmmoInGreenZone) {
                    continue;
                }

                if (is_null($nearAmmo) || $distanceToNearAmmo > $distanceFromUnitToAmmo) {
                    $distanceToNearAmmo = $distanceFromUnitToAmmo;
                    $nearAmmo = $ammo;
                }
            }

            $this->nearestAmmoForMyUnits[$unit->id][$weaponTypeIndex] = $nearAmmo;
        }
    }








    private function deleteFromHistoryWeapon(Loot $loot): void
    {
        /** @var Weapon $weapon */
        $weapon = $loot->item;
        if (isset($this->historyWeapon[$weapon->typeIndex][$loot->id])) {
            unset($this->historyWeapon[$weapon->typeIndex][$loot->id]);
        }
    }

    private function deleteFromHistoryPot(Loot $loot): void
    {
        if (isset($this->historyPot[$loot->id])) {
            unset($this->historyPot[$loot->id]);
        }
    }

    private function deleteFromHistoryAmmo(Loot $loot): void
    {
        /** @var Ammo $ammo */
        $ammo = $loot->item;
        if (isset($this->historyAmmo[$ammo->weaponTypeIndex][$loot->id])) {
            unset($this->historyAmmo[$ammo->weaponTypeIndex][$loot->id]);
        }
    }







    private function missBulletFilter(Unit $unit, Vec2 $targetPosition):Vec2 {
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
        foreach ($this->projectiles as $projectile) {
            if (Helper::isIntersectionLineAndCircle($projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y), $unit->position, $userFictiveRadius)) {
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, $this->MC->black02));}

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
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, $this->MC->white01));}
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
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $position], 0.1, $this->MC->green05));}
            return $this->goToPosition($unit, $position);
        }

        return $targetPosition;
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

class MyWeapon {
    const PISTOL = 0;
    const GUN = 1;
    const SNIPER = 2;

    public static function getName(int $weaponId): string
    {
        switch ($weaponId){
            case self::PISTOL: return "Pistol";
            case self::GUN: return "Gun";
            case self::SNIPER: return "Sniper";
            default: return "Undefined";
        }
    }
}

class MyAction {
    const TYPE_USE_POT = 10;
    const RAISE_AMMO_SNIPER = 20;
    const RAISE_WEAPON_SNIPER = 30;
    const RAISE_POT = 40;
    const SHOOT_TO_TARGET_ENEMY = 50;
    const DO_NOTHING = 60;

    const LOOK_AROUND_BY_TIMER = 70;
    const LOOK_AT_TARGET_ENEMY = 80;
    const LOOK_AROUND = 90;
    const LOOK_AT_UNIT_FORWARD = 91;
    const LOOK_AT_UNIT_FORWARD_WHILE_MISS_BULLET = 92;

    const TRY_TO_MISS_BULLET = 100;
    const GO_OUT_FROM_GREY_ZONE = 110;
    const GO_TO_WEAPON_SNIPER = 120;
    const GO_TO_AMMO_SNIPER = 130;
    const GO_TO_POT = 140;
    const STAY = 150;
}


class MyColor {
    public Color $blue01;
    public Color $blue05;
    public Color $blue1;

    public Color $black01;
    public Color $black02;
    public Color $black05;
    public Color $black1;

    public Color $lightGreen01;

    public Color $green01;
    public Color $green02;
    public Color $green05;
    public Color $green1;

    public Color $white01;
    public Color $white05;
    public Color $white1;

    public Color $red01;
    public Color $red05;
    public Color $red1;

    public Color $aqua01;
    public Color $aqua05;
    public Color $aqua1;

    public function __construct()
    {
        $this->blue01 = new Color(0, 0, 255, 0.1);
        $this->blue05 = new Color(0, 0, 255, 0.5);
        $this->blue1 = new Color(0, 0, 255, 1);

        $this->black01 = new Color(0, 0, 0, 0.1);
        $this->black02 = new Color(0, 0, 0, 0.2);
        $this->black05 = new Color(0, 0, 0, 0.5);
        $this->black1 = new Color(0, 0, 0, 1);

        $this->lightGreen01 = new Color(0, 50, 0, 0.1);

        $this->green01 = new Color(0, 255, 0, 0.1);
        $this->green02 = new Color(0, 255, 0, 0.2);
        $this->green05 = new Color(0, 255, 0, 0.5);
        $this->green1 = new Color(0, 255, 0, 1);

        $this->white01 = new Color(255, 255, 255, 0.1);
        $this->white05 = new Color(255, 255, 255, 0.5);
        $this->white1 = new Color(255, 255, 255, 1);

        $this->red01 = new Color(255, 0, 0, 0.1);
        $this->red05 = new Color(255, 0, 0, 0.5);
        $this->red1 = new Color(255, 0, 0, 1);

        $this->aqua01 = new Color(0, 255, 255, 0.1);
        $this->aqua05 = new Color(0, 255, 255, 0.5);
        $this->aqua1 = new Color(0, 255, 255, 1);
    }
}
