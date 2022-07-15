<?php

use Debugging\DebugData\Circle;
use Model\Game;
use Model\Item\Weapon;
use Model\Loot;
use Model\Unit;
use Model\Vec2;

class MyWeapon implements CommonData, EveryTick, EveryUnit
{
    const PISTOL = 0;   //MagicWand
    const GUN = 1;      //Staff
    const SNIPER = 2;   //Bow

    private ?DebugInterface $debugInterface = null;
    private Vec2 $currentZoneCentre;
    private float $currentZoneRadius = 0;

    /**
     * Оружие, которое видят все мои юниты. Пистолеты
     *
     * @var array | Loot[]
     */
    private array $visiblePistol = [];

    /**
     * Оружие, которое видят все мои юниты. Автоматы
     * @var array | Loot[]
     */
    private array $visibleGun = [];

    /**
     * Оружие, которое видят все мои юниты. Снайперская
     * @var array | Loot[]
     */
    private array $visibleSniper = [];

    /**
     * Оружие, которое когда-либо видели все мои юниты. Пистолеты
     * @var array | Loot[]
     */
    private array $historyPistol = [];

    /**
     * Оружие, которое когда-либо видели все мои юниты. Автоматы
     * @var array | Loot[]
     */
    private array $historyGun = [];

    /**
     * Оружие, которое когда-либо видели все мои юниты. Снайперки
     * @var array | Loot[]
     */
    private array $historySniper = [];

    /**
     * Ближайший пистолет для каждого юнита
     *
     * @var array | Loot[]
     */
    private array $nearestPistolForMyUnits;

    /**
     * Ближайший автомат для каждого юнита
     *
     * @var array | Loot[]
     */
    private array $nearestGunForMyUnits;

    /**
     * Ближайшая снайперка для каждого юнита
     *
     * @var array | Loot[]
     */
    private array $nearestSniperForMyUnits;

    public function __construct()
    {
        $this->currentZoneCentre = new Vec2(0,0);
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->currentZoneCentre = $game->zone->currentCenter;
        $this->currentZoneRadius = $game->zone->currentRadius;
        $this->debugInterface = $debugInterface;
    }

    public function everyTick(): void
    {
        $this->visiblePistol = [];
        $this->visibleGun = [];
        $this->visibleSniper = [];

        foreach ($this->historyPistol as $historyPistol) {
            if (!is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle(new Vec2($historyPistol->position->x, $historyPistol->position->y + 0.5), 0.2, MyColor::getColor(MyColor::BLACK_1)));
            }
        }
        foreach ($this->historyGun as $historyGun) {
            if (!is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle(new Vec2($historyGun->position->x, $historyGun->position->y + 0.5), 0.2, MyColor::getColor(MyColor::BLACK_1)));
            }
        }
        foreach ($this->historyGun as $historySniper) {
            if (!is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle(new Vec2($historySniper->position->x, $historySniper->position->y + 0.5), 0.2, MyColor::getColor(MyColor::BLACK_1)));
            }
        }
    }

    public function everyUnit(Unit $unit): void
    {
        $this->setNearestWeaponForUnit($unit);
    }

    /**
     * Парсинг оружия
     *
     * @param Loot $weapon
     */
    public function setWeapon(Loot $weapon): void
    {
        $this->setVisibleWeapon($weapon);
        $this->setHistoryWeapon($weapon);

        if (!is_null($this->debugInterface)) {
            $this->debugInterface->add(new Circle(new Vec2($weapon->position->x, $weapon->position->y + 0.5), 0.1, MyColor::getColor(MyColor::AQUA_1)));
        }//Отметка, что юнит видит лут
    }

    /**
     * Запоминаю все оружие которое вижу
     *
     * @param Loot $weapon
     */
    private function setVisibleWeapon(Loot $weapon): void
    {
        /** @var Weapon $weaponType */
        $weaponType = $weapon->item;

        switch ($weaponType->typeIndex) {
            case self::PISTOL:
                $this->visiblePistol[] = $weapon;
                break;
            case self::GUN:
                $this->visibleGun[] = $weapon;
                break;
            case self::SNIPER:
                $this->visibleSniper[] = $weapon;
                break;
        }
    }

    /**
     * Сохраняю все оружие которое вижу в историю
     *
     * @param Loot $weapon
     */
    private function setHistoryWeapon(Loot $weapon): void
    {
        /** @var Weapon $weaponType */
        $weaponType = $weapon->item;

        switch ($weaponType->typeIndex) {
            case self::PISTOL:
                $this->historyPistol[$weapon->id] = $weapon;
                break;
            case self::GUN:
                $this->historyGun[$weapon->id] = $weapon;
                break;
            case self::SNIPER:
                $this->historySniper[$weapon->id] = $weapon;
                break;
        }
    }

    /**
     * Определит ближайшее оружие для юнита
     *
     * @param Unit $unit
     */
    private function setNearestWeaponForUnit(Unit $unit): void
    {
        $distanceToNearWeapon = null;

        $nearWeapon = null;
        foreach ($this->historyPistol as $weapon) {
            $distanceFromUnitToWeapon = Helper::getDistance($unit->position, $weapon->position);
            if (!Helper::isPointInCircle($this->currentZoneCentre, $this->currentZoneRadius - MyCommonConst::GREEN_ZONE_RADIUS, $weapon->position)) { //check Green Zone
                continue;
            }
            if (is_null($nearWeapon) || $distanceToNearWeapon > $distanceFromUnitToWeapon) {
                $distanceToNearWeapon = $distanceFromUnitToWeapon;
                $nearWeapon = $weapon;
            }
        }
        $this->nearestPistolForMyUnits[$unit->id] = $nearWeapon;

        $nearWeapon = null;
        foreach ($this->historyGun as $weapon) {
            $distanceFromUnitToWeapon = Helper::getDistance($unit->position, $weapon->position);
            if (!Helper::isPointInCircle($this->currentZoneCentre, $this->currentZoneRadius - MyCommonConst::GREEN_ZONE_RADIUS, $weapon->position)) { //check Green Zone
                continue;
            }
            if (is_null($nearWeapon) || $distanceToNearWeapon > $distanceFromUnitToWeapon) {
                $distanceToNearWeapon = $distanceFromUnitToWeapon;
                $nearWeapon = $weapon;
            }
        }
        $this->nearestGunForMyUnits[$unit->id] = $nearWeapon;

        $nearWeapon = null;
        foreach ($this->historySniper as $weapon) {
            $distanceFromUnitToWeapon = Helper::getDistance($unit->position, $weapon->position);
            if (!Helper::isPointInCircle($this->currentZoneCentre, $this->currentZoneRadius - MyCommonConst::GREEN_ZONE_RADIUS, $weapon->position)) { //check Green Zone
                continue;
            }
            if (is_null($nearWeapon) || $distanceToNearWeapon > $distanceFromUnitToWeapon) {
                $distanceToNearWeapon = $distanceFromUnitToWeapon;
                $nearWeapon = $weapon;
            }
        }
        $this->nearestSniperForMyUnits[$unit->id] = $nearWeapon;
    }

    /**
     * Вернет Название оружия по его индексу
     *
     * @param int $weaponIndex
     * @return string
     */
    public static function getWeaponName(int $weaponIndex): string
    {
        switch ($weaponIndex) {
            case self::PISTOL:
                return "Pistol";
            case self::GUN:
                return "Gun";
            case self::SNIPER:
                return "Sniper";
            default:
                return "Undefined";
        }
    }
}