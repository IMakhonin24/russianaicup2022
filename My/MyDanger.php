<?php

use Debugging\DebugData\Circle;
use Model\Constants;
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

    private float $maxUnitHealth = 0;
    private float $maxShield = 0;
    private float $unitRadius = 0;

    private MySound $mySound;
    private MyUnit $myUnit;
    private ?DebugInterface $debugInterface = null;

    /**
     * @var array | int[]
     */
    private array $dangerLevelForMyUnit = [];

    public function __construct(MySound $mySound, MyUnit $myUnit, Constants $constants)
    {
        $this->maxUnitHealth = $constants->unitHealth;
        $this->maxShield = $constants->maxShield;
        $this->unitRadius = $constants->unitRadius;
        $this->mySound = $mySound;
        $this->myUnit = $myUnit;
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->debugInterface = $debugInterface;
    }

    public function everyTick(): void
    {
        $this->dangerLevelForMyUnit = [];
    }

    public function everyUnit($unit): void
    {
        $this->defineDangerLevel($unit);
    }

    private function defineDangerLevel(Unit $unit): void
    {
        /*
         * Ситуации:
         * Вокруг >=3 врагов
         * Мало здоровья    -   2
         * Звук попаданий рядом 2
         * Мало брони       -   2
         *
         *
         * Вокруг >=1 врагов
         * Звук шагов       -   1
         * Звук выстрелов   -   1
         *
         * ...              -   0
         */

        //============= Врагов >=3 2 ===============
        $countEnemyInSaveZone = 0;
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle($unit->position, MyCommonConst::DANGER_UNIT_SAVE_PERSONAL_ZONE, MyColor::getColor(MyColor::YELLOW_01)));}
        foreach ($this->myUnit->historyEnemies as $historyEnemy) {
            if (Helper::isPointInCircle($unit->position,MyCommonConst::DANGER_UNIT_SAVE_PERSONAL_ZONE, $historyEnemy->unit->position)){
                $countEnemyInSaveZone++;
            }
        }
        if ($countEnemyInSaveZone >= 3){
            $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_2; return;
        }
        //============= Врагов >=3 2 ===============


        //============= Мало здоровья 2 ===============
        if ($unit->health < ($this->maxUnitHealth * MyCommonConst::DANGER_LEVEL_HEALTH_PERCENT) / 100){
            $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_2; return;
        }
        //============= Мало здоровья 2 ===============


        //============= Мало брони ===============
        if ($unit->shield < ($this->maxShield * MyCommonConst::DANGER_LEVEL_SHIELD_PERCENT) / 100){
            $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_2; return;
        }
        //============= Мало брони 2 ===============


        //============= Рядом попадание 2 ===============
//        if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle($unit->position, MyCommonConst::DANGER_DISTANCE_WEAPON_HIT, MyColor::getColor(MyColor::YELLOW_01)));}
        foreach ($this->mySound->soundsPistolHit as $sound) {
            if (Helper::isPointInCircle($unit->position, MyCommonConst::DANGER_DISTANCE_WEAPON_HIT, $sound->sound->position)){
                $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_2; return;
            }
        }
        foreach ($this->mySound->soundsGunHit as $sound) {
            if (Helper::isPointInCircle($unit->position, MyCommonConst::DANGER_DISTANCE_WEAPON_HIT, $sound->sound->position)){
                $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_2; return;
            }
        }
        foreach ($this->mySound->soundsSniperHit as $sound) {
            if (Helper::isPointInCircle($unit->position, MyCommonConst::DANGER_DISTANCE_WEAPON_HIT, $sound->sound->position)){
                $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_2; return;
            }
        }
        //============= Рядом попадание 2 ===============


        //============= Рядом звук выстрелов 1 ===============
//        if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle($unit->position, MyCommonConst::DANGER_DISTANCE_WEAPON_SHOOT, MyColor::getColor(MyColor::YELLOW_01)));}
        foreach ($this->mySound->soundsPistolShoot as $sound) {
            if (Helper::isPointInCircle($unit->position, MyCommonConst::DANGER_DISTANCE_WEAPON_SHOOT, $sound->sound->position)){
                $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_1; return;
            }
        }
        foreach ($this->mySound->soundsGunShoot as $sound) {
            if (Helper::isPointInCircle($unit->position, MyCommonConst::DANGER_DISTANCE_WEAPON_SHOOT, $sound->sound->position)){
                $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_1; return;
            }
        }
        foreach ($this->mySound->soundsSniperShoot as $sound) {
            if (Helper::isPointInCircle($unit->position, MyCommonConst::DANGER_DISTANCE_WEAPON_SHOOT, $sound->sound->position)){
                $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_1; return;
            }
        }
        //============= Рядом звук выстрелов 1 ===============

        //============= Рядом звук шагов 1 ===============
        if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle($unit->position, MyCommonConst::DANGER_DISTANCE_STEP_SOUND, MyColor::getColor(MyColor::YELLOW_01)));}
        foreach ($this->mySound->soundsPistolShoot as $sound) {
            if (Helper::isPointInCircle($unit->position, MyCommonConst::DANGER_DISTANCE_STEP_SOUND, $sound->sound->position)){
                $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_1; return;
            }
        }
        //============= Рядом звук шагов 1 ===============

        //============= Врагов >=1 1 ===============
        if ($countEnemyInSaveZone >= 1){
            $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_1; return;
        }
        //============= Врагов >=1 1 ===============






        $this->dangerLevelForMyUnit[$unit->id] = self::LEVEL_0;
    }

    public function getDangerLevel(Unit $unit): int
    {
        if (isset($this->dangerLevelForMyUnit[$unit->id]) && $this->dangerLevelForMyUnit[$unit->id] == self::LEVEL_2){
            $color = MyColor::RED_1;
        }elseif (isset($this->dangerLevelForMyUnit[$unit->id]) && $this->dangerLevelForMyUnit[$unit->id] == self::LEVEL_1){
            $color = MyColor::YELLOW_1;
        }else{
            $color = MyColor::GREEN_1;
        }

        if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle($unit->position, $this->unitRadius, MyColor::getColor($color)));}

        return $this->dangerLevelForMyUnit[$unit->id];
    }

}