<?php

use Debugging\DebugData\Circle;
use Debugging\DebugData\PlacedText;
use Debugging\DebugData\PolyLine;
use Model\ActionOrder;
use Model\ActionOrder\Aim;
use Model\ActionOrder\Pickup;
use Model\ActionOrder\UseShieldPotion;
use Model\Constants;
use Model\Game;
use Model\Order;
use Model\Unit;
use Model\UnitOrder;
use Model\Vec2;

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

class MyStrategyOld
{
    private Constants $constants;

    private MyObstacles $myObstacles;
    private MyLoot $myLoot;
    private MyUnit $myUnit;
    private MyProjectiles $myProjectiles;
    private MySound $mySound;

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

    private ?DebugInterface $debugInterface;

    function __construct(Constants $constants)
    {
        $this->constants = $constants;//todo удалить
        $this->myUnit = new MyUnit($constants);
        $this->myLoot = new MyLoot($constants);
        $this->myObstacles = new MyObstacles($constants);
        $this->myProjectiles = new MyProjectiles($constants);
        $this->mySound = new MySound($constants);
    }

    private function everyUnit(Unit $unit): void
    {
        $this->actionForMyUnitsFoot = [];
        $this->actionForMyUnitsEye = [];
        $this->actionForMyUnitsAction = [];
        $this->actionTypeForMyUnitsFoot = [];
        $this->actionTypeForMyUnitsEye = [];
        $this->actionTypeForMyUnitsAction = [];

        $this->myUnit->everyUnit($unit);
        $this->myLoot->everyUnit($unit);
        $this->myObstacles->everyUnit($unit);
    }

    function getOrder(Game $game, ?DebugInterface $debugInterface): Order
    {
        $this->debugInterface = $debugInterface; // todo


        $order = [];
        if (!is_null($this->debugInterface)){
            $this->debugInterface->add(new Circle(
                $game->zone->currentCenter,
                $game->zone->currentRadius - 25,
                MyColor::getColor(MyColor::LIGHT_GREEN_01)
            ));
        }

        foreach ($this->myUnit->myUnits as $unit) {
            $this->everyUnit($unit);

            $order[$unit->id] = new UnitOrder(
                $this->footController($game, $unit),
                $this->eyeController($game, $unit),
                $this->actionController($game, $unit)
            );

            //Вывожу параметры юнитов
            foreach ($unit->ammo as $ammoType => $ammoCnt) {
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y + 2 + ($ammoType * -0.3)), "Ammo[".$ammoType."] = ".$ammoCnt, new Vec2(0, 0), 0.3, MyColor::getColor(MyColor::BLUE_1)));}
            }
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y + 1), "Weapon: " . MyWeapon::getWeaponName($unit->weapon), new Vec2(0, 0), 0.3, MyColor::getColor(MyColor::BLUE_1)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y+0.6), "Pots: " . $unit->shieldPotions . "/" .$this->constants->maxShieldPotionsInInventory, new Vec2(0, 0), 0.3, MyColor::getColor(MyColor::BLUE_1)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y-1.3), "DistanceToTargetEnemy: ".(isset($this->targetEnemyForMyUnits[$unit->id])?Helper::getDistance($unit->position, $this->targetEnemyForMyUnits[$unit->id]->position) : 'null'), new Vec2(0, 0), 0.3, MyColor::getColor(MyColor::BLUE_1)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y-1.6), "Velocity: (". (int)$unit->velocity->x.";".(int)$unit->velocity->y.") = ".(int)Helper::getDistance($unit->position,new Vec2($unit->position->x + $unit->velocity->x,$unit->position->y + $unit->velocity->y)), new Vec2(0, 0), 0.3, MyColor::getColor(MyColor::BLUE_1)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x - 4, $unit->position->y+0.2), "In person area: ". count($this->myUnit->enemyInPersonalAreaForMyUnits[$unit->id]), new Vec2(0, 0), 0.3, MyColor::getColor(MyColor::BLUE_1)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle($unit->position, $this->constants->unitRadius * MyCommonConst::COEFFICIENT_PERSONAL_AREA, MyColor::getColor(MyColor::YELLOW_01)));}//PersonalArea Circle
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
                MyColor::getColor(MyColor::GREEN_02)
            ));
        }

        foreach ($this->myProjectiles->allProjectiles as $projectile) {
            if (Helper::isIntersectionLineAndCircle($projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y), $unit->position, $userFictiveRadius)) {
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, MyColor::getColor(MyColor::BLACK_02)));}

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
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, MyColor::getColor(MyColor::WHITE_01)));}
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
            foreach ($this->myProjectiles->allProjectiles as $projectile) {
                if (Helper::isIntersectionLineAndCircle($projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y), $nextUnitPosition1, $this->constants->unitRadius)) {
                    $numberHit1++;
                }
                if (Helper::isIntersectionLineAndCircle($projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y), $nextUnitPosition2, $this->constants->unitRadius)) {
                    $numberHit2++;
                }
            }
            $position = ($numberHit1 >= $numberHit2) ? $nextUnitPosition2 : $nextUnitPosition1;
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::TRY_TO_MISS_BULLET;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $position], 0.1, MyColor::getColor(MyColor::BLUE_05)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Увороты", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::BLUE_1)));}
            return $this->goToPosition($unit, $position);
        }
        //===================Увороты========================


        //===================Уходим от зоны========================
        if (!Helper::isPointInCircle($game->zone->currentCenter, $game->zone->currentRadius - 25, $unit->position)) {
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::GO_OUT_FROM_GREY_ZONE;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Ухожу из серой зоны", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::BLUE_1)));}
            return $this->goToPosition($unit, new Vec2(0, 0));
        }
        //===================Уходим от зоны========================

        //===================Убегаем от толпы========================
        if (count($this->myUnit->enemyInPersonalAreaForMyUnits[$unit->id]) >= 2){
            $positions = [];
            foreach ($this->myUnit->enemyInPersonalAreaForMyUnits[$unit->id] as $enemyInPersonalAreaForMyUnit) {
                $positions[] = $enemyInPersonalAreaForMyUnit->position;
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $enemyInPersonalAreaForMyUnit->position], 0.3, MyColor::getColor(MyColor::RED_05)));}
            }

            $goOutPosition = Helper::getAverageVectorFromOneCentre($unit->position, $positions);
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::GO_OUT_FROM_A_LOT_OF_UNIT;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $goOutPosition], 0.3, MyColor::getColor(MyColor::AQUA_05)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Убегаю от толпы", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::BLUE_1)));}
            return $this->goToPosition($unit, $goOutPosition);
        }
        //===================Убегаем от толпы========================

        //===================Ищем снайперку========================
        if ($unit->weapon < MyWeapon::SNIPER && $unit->ammo[MyWeapon::SNIPER] > 0 && isset($this->nearestWeaponForMyUnits[$unit->id][MyWeapon::SNIPER])){
            $weaponSniper = $this->nearestWeaponForMyUnits[$unit->id][MyWeapon::SNIPER];
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::GO_TO_WEAPON_SNIPER;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $weaponSniper->position], 0.1, MyColor::getColor(MyColor::BLUE_05)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Иду к ближ. снайп. оруж.", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::BLUE_1)));}
            return $this->goToPosition($unit, $weaponSniper->position);
        }
        //===================Ищем снайперку========================

        //===================Идем брать ближайшие патроны для снайперки========================
        if ($unit->ammo[MyWeapon::SNIPER] < 10 && isset($this->nearestAmmoForMyUnits[$unit->id][MyWeapon::SNIPER])){
            $weaponAmmoSniper = $this->nearestAmmoForMyUnits[$unit->id][MyWeapon::SNIPER];
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::GO_TO_AMMO_SNIPER;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $weaponAmmoSniper->position], 0.1, MyColor::getColor(MyColor::BLUE_05)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Иду к ближ. патрон. cнайп. оруж.", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::BLUE_1)));}
            return $this->goToPosition($unit, $weaponAmmoSniper->position);
        }
        //===================Идем брать ближайшие патроны для снайперки========================

        //===================Идем искать зелья========================
        if ($unit->shieldPotions < $this->constants->maxShieldPotionsInInventory && isset($this->nearestPotForMyUnits[$unit->id])) {
            $pot = $this->nearestPotForMyUnits[$unit->id];
            $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::GO_TO_POT;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $pot->position], 0.1, MyColor::getColor(MyColor::BLUE_05)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Иду к ближ. поту", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::BLUE_1)));}
            return $this->goToPosition($unit, $pot->position);
        }
        //===================Идем искать зелья========================

        //todo Не стоим. Идем по спирали
        $this->actionTypeForMyUnitsFoot[$unit->id] = MyAction::STAY;
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 2), "Стою на месте", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::BLUE_1)));}
        return $this->goToPosition($unit, $unit->position);
    }

    private function eyeController(Game $game, Unit $unit): Vec2
    {
        //===================Первый осмотр территории========================
        if($game->currentTick < 40){
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AROUND_FIRST;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Первый осмотр территории", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::RED_1)));}
            return new Vec2($unit->direction->y, -$unit->direction->x);
        }
        //===================Первый осмотр территории========================

        //===================Смотрю вперед когда уворачиваюсь от пуль========================
        if ((isset($this->actionTypeForMyUnitsFoot[$unit->id]) && $this->actionTypeForMyUnitsFoot[$unit->id] == MyAction::TRY_TO_MISS_BULLET) && isset($this->actionForMyUnitsFoot[$unit->id])){
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AT_UNIT_FORWARD_WHILE_MISS_BULLET;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю по ходу движения во время уворотов", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::RED_1)));}
            return Helper::getVectorAB($unit->position, $this->actionForMyUnitsFoot[$unit->id]);
        }
        //===================Смотрю вперед когда уворачиваюсь от пуль========================

        //===================Смотрю на звук шагов========================
        //если нет таргета или таргет есть но дистанция до звука ближе чем до таргета
        //Если есть звук И (нет таргета ИЛИ есть таргет дальше чем звук)
        if (!empty($this->soundsSteps)){
            $soundStep = $this->soundsSteps[count($this->soundsSteps)-1];
            if (!isset($this->targetEnemyForMyUnits[$unit->id]) || (isset($this->targetEnemyForMyUnits[$unit->id]) && Helper::getDistance($unit->position, $this->targetEnemyForMyUnits[$unit->id]->position) > 100 && Helper::getDistance($unit->position, $this->targetEnemyForMyUnits[$unit->id]->position) > Helper::getDistance($unit->position, $soundStep->sound->position))){
                $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_SOUND_STEPS;
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю на звук шагов", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::RED_1)));}
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $soundStep->sound->position], 0.1, MyColor::getColor(MyColor::RED_05)));}
                return Helper::getVectorAB($unit->position, $soundStep->sound->position);
            }
        }
        //===================Смотрю на звук шагов========================

        //===================Смотрю на target========================
        if (isset($this->targetEnemyForMyUnits[$unit->id]) && $this->actionTypeForMyUnitsFoot[$unit->id] !== MyAction::GO_OUT_FROM_A_LOT_OF_UNIT) {
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю на targetEnemy", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::RED_1)));}
            $targetForMyUnit = $this->targetEnemyForMyUnits[$unit->id];
            //куда идет цель
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$targetForMyUnit->position, new Vec2($targetForMyUnit->position->x + $targetForMyUnit->velocity->x, $targetForMyUnit->position->y + $targetForMyUnit->velocity->y)], 0.1, MyColor::getColor(MyColor::RED_05)));}
            $currentDistanceToTarget = Helper::getDistance($unit->position, $targetForMyUnit->position);
            $k = $currentDistanceToTarget < 100 ? 100 : ($currentDistanceToTarget < 200 ? 2 : (2));
            $targetForMyUnit = new Vec2($targetForMyUnit->position->x + ($targetForMyUnit->velocity->x / $k), $targetForMyUnit->position->y + ($targetForMyUnit->velocity->y / $k));
            $this->actionForMyUnitsEye[$unit->id] = $targetForMyUnit;
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AT_TARGET_ENEMY;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $targetForMyUnit], 0.1, MyColor::getColor(MyColor::RED_05)));}
            return Helper::getVectorAB($unit->position, $targetForMyUnit);
        }
        //===================Смотрю на target========================

        //===================Смотрю на звук выстрелов========================
        if (!empty($this->soundsPistolShoot) || !empty($this->soundsGunShoot) || !empty($this->soundsSniperShoot)){
            $soundShoot = null;
            if (is_null($soundShoot) && !empty($this->soundsPistolShoot)) $soundShoot = $this->soundsPistolShoot[count($this->soundsPistolShoot)-1];
            if (is_null($soundShoot) && !empty($this->soundsGunShoot)) $soundShoot = $this->soundsGunShoot[count($this->soundsGunShoot)-1];
            if (is_null($soundShoot) && !empty($this->soundsSniperShoot)) $soundShoot = $this->soundsSniperShoot[count($this->soundsSniperShoot)-1];
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_SOUND_SHOOT;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю на звук выстрелов", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::RED_1)));}
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, $soundShoot->sound->position], 0.1, MyColor::getColor(MyColor::RED_05)));}
            return Helper::getVectorAB($unit->position, $soundShoot->sound->position);
        }
        //===================Смотрю на звук выстрелов========================

        //===================Осмотреться по таймеру========================
        if (($game->currentTick / $this->constants->ticksPerSecond) % 5 == 0) {
            $targetPosition = new Vec2($unit->direction->y, -$unit->direction->x);
            $this->actionForMyUnitsEye[$unit->id] = $targetPosition;
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AROUND_BY_TIMER;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Осмотреться по таймеру", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::RED_1)));}
            return $targetPosition;
        }
        //===================Осмотреться по таймеру========================

        //===================Смотрю по ходу движения========================
        if ((isset($this->actionTypeForMyUnitsFoot[$unit->id]) && $this->actionTypeForMyUnitsFoot[$unit->id] != MyAction::STAY) && isset($this->actionForMyUnitsFoot[$unit->id])){
            $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AT_UNIT_FORWARD;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю по ходу движения", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::RED_1)));}
            return Helper::getVectorAB($unit->position, $this->actionForMyUnitsFoot[$unit->id]);
        }
        //===================Смотрю по ходу движения========================

        //===================Смотрю по кругу========================
        $this->actionTypeForMyUnitsEye[$unit->id] = MyAction::LOOK_AROUND;
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y + 1), "Смотрю по куругу", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::RED_1)));}
        return new Vec2($unit->direction->y, -$unit->direction->x);
        //===================Смотрю по кругу========================
    }

    private function actionController(Game $game, Unit $unit): ?ActionOrder
    {
        //===================Применяю пот========================
        if ($unit->shieldPotions > 0 && $unit->shield + $this->constants->shieldPerPotion < $this->constants->maxShield) {
            $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::TYPE_USE_POT;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Применяю пот", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::GREEN_1)));}
            return new UseShieldPotion();
        }
        //===================Применяю пот========================

        //===================Подбираем патроны для снайперки========================
        if (isset($this->nearestAmmoForMyUnits[$unit->id][MyWeapon::SNIPER])) {
            $weaponAmmoSniper = $this->nearestAmmoForMyUnits[$unit->id][MyWeapon::SNIPER];
            $distanceFromUnitToWeaponAmmo = Helper::getDistance($unit->position, $weaponAmmoSniper->position);
            if ($distanceFromUnitToWeaponAmmo < $this->constants->unitRadius) {
                $this->myLoot->myAmmo->deleteFromHistory($weaponAmmoSniper);
                $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::RAISE_AMMO_SNIPER;
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Подбираю патроны для снайперки", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::GREEN_1)));}
                return new Pickup($weaponAmmoSniper->id);
            }
        }
        //===================Подбираем патроны для снайперки========================

        //===================Подбираем снайперку========================
        if (isset($this->nearestWeaponForMyUnits[$unit->id][MyWeapon::SNIPER])) {
            $weaponSniper = $this->nearestWeaponForMyUnits[$unit->id][MyWeapon::SNIPER];
            $distanceFromUnitTWeapon = Helper::getDistance($unit->position, $weaponSniper->position);
            if ($distanceFromUnitTWeapon < $this->constants->unitRadius) {
                $this->myLoot->myWeapon->deleteFromHistory($weaponSniper);
                $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::RAISE_WEAPON_SNIPER;
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Подбираю снайперку", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::GREEN_1)));}
                return new Pickup($weaponSniper->id);
            }
        }
        //===================Подбираем снайперку========================

        //===================Подбираем пот========================
        if ($unit->shieldPotions < $this->constants->maxShieldPotionsInInventory && isset($this->nearestPotForMyUnits[$unit->id])) {
            $pot = $this->nearestPotForMyUnits[$unit->id];
            $distanceFromUnitToPot = Helper::getDistance($unit->position, $pot->position);
            if ($distanceFromUnitToPot < $this->constants->unitRadius) {
                $this->myLoot->myPot->deleteFromHistory($pot);
                $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::RAISE_POT;
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Подбираю пот", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::GREEN_1)));}
                return new Pickup($pot->id);
            }
        }
        //===================Подбираем пот========================

        //===================Стреляем по таргет юниту========================
        if ((isset($this->targetEnemyForMyUnits[$unit->id]) && isset($this->actionTypeForMyUnitsEye[$unit->id]) && $this->actionTypeForMyUnitsEye[$unit->id] == MyAction::LOOK_AT_TARGET_ENEMY) && Helper::getDistance($unit->position, $this->targetEnemyForMyUnits[$unit->id]->position) < 500) {
            $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::SHOOT_TO_TARGET_ENEMY;
            if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Стреляю в targetEnemy", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::GREEN_1)));}
            return new Aim(true);
        }
        //===================Стреляем по таргет юниту========================

        //===================Ничего не делаю========================
        $this->actionTypeForMyUnitsAction[$unit->id] = MyAction::DO_NOTHING;
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($unit->position->x + 2, $unit->position->y), "Ничего не делаю", new Vec2(0, 0), 0.5, MyColor::getColor(MyColor::GREEN_1)));}
        return null;
        //===================Ничего не делаю========================
    }


    public function goToPosition(Unit $unit, Vec2 $targetPosition): Vec2
    {
        $this->actionForMyUnitsFoot[$unit->id] = $targetPosition;
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle($targetPosition, 0.5, MyColor::getColor(MyColor::BLUE_05)));}
        if (Helper::isPointInCircle($unit->position, $this->constants->unitRadius, $targetPosition)) {
            return new Vec2(0, 0);
        } else {
            $vec = Helper::getVectorAB($unit->position, $targetPosition);
            $vec = new Vec2($this->constants->maxUnitForwardSpeed * $vec->x, $this->constants->maxUnitForwardSpeed * $vec->y);//увеличение вектора, для скорости юнита
            return Helper::getVectorAB($unit->position, new Vec2($unit->position->x + $vec->x, $unit->position->y + $vec->y) );
        }
    }

    function debugUpdate(DebugInterface $debug_interface)
    {
    }

    function finish()
    {
    }
}