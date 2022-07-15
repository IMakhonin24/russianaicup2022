<?php

use Debugging\DebugData\Circle;
use Model\Game;
use Model\Loot;
use Model\Unit;
use Model\Vec2;

class MyPot implements CommonData, EveryTick
{
    private ?DebugInterface $debugInterface = null;
    private Vec2 $currentZoneCentre;
    private float $currentZoneRadius = 0;

    /**
     * Аптечки, которые видят все мои юниты
     *
     * @var array | Loot[]
     */
    private array $visiblePot = [];

    /**
     * Аптечки, которые когда-либо видели все мои юниты
     * @var array | Loot[]
     */
    private array $historyPot = [];

    /**
     * Ближайшая аптечка для юнита
     *
     * @var array | Loot[]
     */
    private array $nearestPotForMyUnits;

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
        foreach ($this->historyPot as $historyPot) {
            if (!is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle(new Vec2($historyPot->position->x, $historyPot->position->y + 0.5), 0.2, MyColor::getColor(MyColor::BLACK_1)));
            }
        }
    }

    public function everyUnit(Unit $unit): void
    {
        $this->setNearestPotForUnit($unit);

    }

    /**
     * Парсинг аптечек
     *
     * @param Loot $pot
     */
    public function setPot(Loot $pot): void
    {
        $this->visiblePot[] = $pot;
        $this->historyPot[$pot->id] = $pot;

        if (!is_null($this->debugInterface)) {
            $this->debugInterface->add(new Circle(new Vec2($pot->position->x, $pot->position->y + 0.5), 0.1, MyColor::getColor(MyColor::AQUA_1)));
        }//Отметка, что юнит видит лут
    }

    /**
     * Определит ближайшую аптечку для юнита
     *
     * @param Unit $unit
     */
    private function setNearestPotForUnit(Unit $unit): void
    {
        $distanceToNearPot = null;

        $nearPot = null;
        foreach ($this->historyPot as $pot) {
            $distanceFromUnitToPot = Helper::getDistance($unit->position, $pot->position);
            if (!Helper::isPointInCircle($this->currentZoneCentre, $this->currentZoneRadius - MyCommonConst::GREEN_ZONE_RADIUS, $pot->position)) { //check Green Zone
                continue;
            }
            if (is_null($nearPot) || $distanceToNearPot > $distanceFromUnitToPot) {
                $distanceToNearPot = $distanceFromUnitToPot;
                $nearPot = $pot;
            }
        }
        $this->nearestPotForMyUnits[$unit->id] = $nearPot;
    }
}