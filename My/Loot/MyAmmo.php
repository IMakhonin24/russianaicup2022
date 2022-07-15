<?php

use Debugging\DebugData\Circle;
use Model\Game;
use Model\Item\Ammo;
use Model\Loot;
use Model\Unit;
use Model\Vec2;

class MyAmmo implements CommonData, EveryTick, EveryUnit
{
    private ?DebugInterface $debugInterface = null;
    private Vec2 $currentZoneCentre;
    private float $currentZoneRadius = 0;

    /**
     * Патроны, которые видят все мои юниты. Пистолеты
     *
     * @var array | Loot[]
     */
    private array $visiblePistolAmmo = [];

    /**
     * Патроны, которые видят все мои юниты. Автоматы
     * @var array | Loot[]
     */
    private array $visibleGunAmmo = [];

    /**
     * Патроны, которые видят все мои юниты. Снайперская
     * @var array | Loot[]
     */
    private array $visibleSniperAmmo = [];

    /**
     * Патроны, которые когда-либо видели все мои юниты. Пистолеты
     * @var array | Loot[]
     */
    private array $historyPistolAmmo = [];

    /**
     * Патроны, которые когда-либо видели все мои юниты. Автоматы
     * @var array | Loot[]
     */
    private array $historyGunAmmo = [];

    /**
     * Патроны, которые когда-либо видели все мои юниты. Снайперки
     * @var array | Loot[]
     */
    private array $historySniperAmmo = [];

    /**
     * Ближайшие патроны к пистолету для юнита
     *
     * @var array | Loot[]
     */
    private array $nearestPistolAmmoForMyUnits;

    /**
     * Ближайшие патроны к автомату для юнита
     *
     * @var array | Loot[]
     */
    private array $nearestGunAmmoForMyUnits;

    /**
     * Ближайшие патроны к снайперке для юнита
     *
     * @var array | Loot[]
     */
    private array $nearestSniperAmmoForMyUnits;

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
        foreach ($this->historyPistolAmmo as $historyPistolAmmo) {
            if (!is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle(new Vec2($historyPistolAmmo->position->x, $historyPistolAmmo->position->y + 0.5), 0.2, MyColor::getColor(MyColor::BLACK_1)));
            }
        }
        foreach ($this->historyGunAmmo as $historyGunAmmo) {
            if (!is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle(new Vec2($historyGunAmmo->position->x, $historyGunAmmo->position->y + 0.5), 0.2, MyColor::getColor(MyColor::BLACK_1)));
            }
        }
        foreach ($this->historySniperAmmo as $historySniperAmmo) {
            if (!is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle(new Vec2($historySniperAmmo->position->x, $historySniperAmmo->position->y + 0.5), 0.2, MyColor::getColor(MyColor::BLACK_1)));
            }
        }
    }

    public function everyUnit(Unit $unit): void
    {
        $this->setNearestWeaponAmmoForUnit($unit);
    }

    /**
     * Парсинг патронов
     *
     * @param Loot $ammo
     */
    public function setAmmo(Loot $ammo): void
    {
        $this->setVisibleAmmo($ammo);
        $this->setHistoryAmmo($ammo);

        if (!is_null($this->debugInterface)) {
            $this->debugInterface->add(new Circle(new Vec2($ammo->position->x, $ammo->position->y + 0.5), 0.1, MyColor::getColor(MyColor::AQUA_1)));
        }//Отметка, что юнит видит лут
    }

    /**
     * Запоминаю все патроны которые вижу
     *
     * @param Loot $ammo
     */
    private function setVisibleAmmo(Loot $ammo): void
    {
        /** @var Ammo $ammoType */
        $ammoType = $ammo->item;

        switch ($ammoType->weaponTypeIndex) {
            case MyWeapon::PISTOL:
                $this->visiblePistolAmmo[] = $ammo;
                break;
            case MyWeapon::GUN:
                $this->visibleGunAmmo[] = $ammo;
                break;
            case MyWeapon::SNIPER:
                $this->visibleSniperAmmo[] = $ammo;
                break;
        }
    }

    /**
     * Сохраняю все патроны которые вижу в историю
     *
     * @param Loot $ammo
     */
    private function setHistoryAmmo(Loot $ammo): void
    {
        /** @var Ammo $ammoType */
        $ammoType = $ammo->item;

        switch ($ammoType->weaponTypeIndex) {
            case MyWeapon::PISTOL:
                $this->historyPistolAmmo[$ammo->id] = $ammo;
                break;
            case MyWeapon::GUN:
                $this->historyGunAmmo[$ammo->id] = $ammo;
                break;
            case MyWeapon::SNIPER:
                $this->historySniperAmmo[$ammo->id] = $ammo;
                break;
        }
    }

    /**
     * Определит ближайшие патроны для юнита
     *
     * @param Unit $unit
     */
    private function setNearestWeaponAmmoForUnit(Unit $unit): void
    {
        $distanceToNearWeaponAmmo = null;

        $nearWeaponAmmo = null;
        foreach ($this->historyPistolAmmo as $ammo) {
            $distanceFromUnitToWeaponAmmo = Helper::getDistance($unit->position, $ammo->position);
            if (!Helper::isPointInCircle($this->currentZoneCentre, $this->currentZoneRadius - MyCommonConst::GREEN_ZONE_RADIUS, $ammo->position)) { //check Green Zone
                continue;
            }
            if (is_null($nearWeaponAmmo) || $distanceToNearWeaponAmmo > $distanceFromUnitToWeaponAmmo) {
                $distanceToNearWeaponAmmo = $distanceFromUnitToWeaponAmmo;
                $nearWeaponAmmo = $ammo;
            }
        }
        $this->nearestPistolAmmoForMyUnits[$unit->id] = $nearWeaponAmmo;

        $nearWeaponAmmo = null;
        foreach ($this->historyGunAmmo as $ammo) {
            $distanceFromUnitToWeaponAmmo = Helper::getDistance($unit->position, $ammo->position);
            if (!Helper::isPointInCircle($this->currentZoneCentre, $this->currentZoneRadius - MyCommonConst::GREEN_ZONE_RADIUS, $ammo->position)) { //check Green Zone
                continue;
            }
            if (is_null($nearWeaponAmmo) || $distanceToNearWeaponAmmo > $distanceFromUnitToWeaponAmmo) {
                $distanceToNearWeaponAmmo = $distanceFromUnitToWeaponAmmo;
                $nearWeaponAmmo = $ammo;
            }
        }
        $this->nearestGunAmmoForMyUnits[$unit->id] = $nearWeaponAmmo;

        $nearWeaponAmmo = null;
        foreach ($this->historySniperAmmo as $ammo) {
            $distanceFromUnitToWeaponAmmo = Helper::getDistance($unit->position, $ammo->position);
            if (!Helper::isPointInCircle($this->currentZoneCentre, $this->currentZoneRadius - MyCommonConst::GREEN_ZONE_RADIUS, $ammo->position)) { //check Green Zone
                continue;
            }
            if (is_null($nearWeaponAmmo) || $distanceToNearWeaponAmmo > $distanceFromUnitToWeaponAmmo) {
                $distanceToNearWeaponAmmo = $distanceFromUnitToWeaponAmmo;
                $nearWeaponAmmo = $ammo;
            }
        }
        $this->nearestSniperAmmoForMyUnits[$unit->id] = $nearWeaponAmmo;
    }
}