<?php

use Debugging\DebugData\Circle;
use Debugging\DebugData\PolyLine;
use Model\Constants;
use Model\Unit;
use Model\Vec2;

class VelocityFilter
{
    private const ENABLE_DEBUG_FILTER = false;

    private Vec2 $targetVelocity;

    private MyObstacles $myObstacles;

    private Unit $unit;

    private Constants $constants;

    private ?DebugInterface $debugInterface;

    public function __construct(Unit $unit, MyObstacles $myObstacles, Constants $constants, ?DebugInterface $debugInterface)
    {
        $this->unit = $unit;
        $this->myObstacles = $myObstacles;
        $this->constants = $constants;
        $this->debugInterface = $debugInterface;
    }

    public function getFilteredVelocity(Vec2 $targetVelocity): Vec2
    {
        $this->targetVelocity = $targetVelocity;
        $this->applyObstacleFilter();
        return $this->targetVelocity;
    }

    private function applyObstacleFilter(): void
    {
        $radiusInCheck = 10;
        if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)) {
            $this->debugInterface->add(new Circle($this->unit->position, $radiusInCheck, MyColor::getColor(MyColor::RED_01)));
        }

        $nearObstacleToVelocity = null;
        $distanceToNearObstacleToVelocity = null;
        //определим камень который пересекает путь юнита
        foreach ($this->myObstacles->obstacles as $obstacle) {
            $distanceObstacleToVelocity = Helper::getDistance(new Vec2($this->unit->position->x + $this->targetVelocity->x, $this->unit->position->y + $this->targetVelocity->y), $obstacle->position);
            if (is_null($nearObstacleToVelocity) || $distanceToNearObstacleToVelocity > $distanceObstacleToVelocity) {
                $distanceToNearObstacleToVelocity = $distanceObstacleToVelocity;
                $nearObstacleToVelocity = $obstacle;
            }
        }

        $oldPointVelocity = new Vec2($this->unit->position->x + $this->targetVelocity->x, $this->unit->position->y + $this->targetVelocity->y);
        if (!is_null($nearObstacleToVelocity) &&
            Helper::isIntersectionLineAndCircle($this->unit->position, $oldPointVelocity, $nearObstacleToVelocity->position, $nearObstacleToVelocity->radius + $this->constants->unitRadius)
        ) {
            if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle(new Vec2($this->unit->position->x + $this->targetVelocity->x, $this->unit->position->y + $this->targetVelocity->y), 0.2, MyColor::getColor(MyColor::AQUA_05)));
            }
            if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle($nearObstacleToVelocity->position, $nearObstacleToVelocity->radius + $this->constants->unitRadius, MyColor::getColor(MyColor::BLUE_05)));
            }

            //Перпендикуляр от центра камня до VelocityTarget
            $perpendicularFromObstacleToVelocityPoint = Helper::getPerpendicularTo($this->unit->position, $this->targetVelocity, $nearObstacleToVelocity->position);
            if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)) {
                $this->debugInterface->add(new PolyLine([$nearObstacleToVelocity->position, $perpendicularFromObstacleToVelocityPoint], 0.1, MyColor::getColor(MyColor::RED_1)));
            }//test

            $pointRecommend = Helper::getPointOnLineABAtDistanceAC(
                $nearObstacleToVelocity->position,
                $perpendicularFromObstacleToVelocityPoint,
                $nearObstacleToVelocity->radius + $this->constants->unitRadius * 3
            );

            if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)) {
                $this->debugInterface->add(new Circle($pointRecommend, 0.2, MyColor::getColor(MyColor::AQUA_05)));
            }

            $vectorRecommend = Helper::getVectorAB($perpendicularFromObstacleToVelocityPoint, $pointRecommend);
            if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)) {
                $this->debugInterface->add(new PolyLine([new Vec2($this->unit->position->x + $this->targetVelocity->x, $this->unit->position->y + $this->targetVelocity->y), new Vec2($this->unit->position->x + $this->targetVelocity->x + $vectorRecommend->x, $this->unit->position->y + $this->targetVelocity->y + $vectorRecommend->y)], 0.1, MyColor::getColor(MyColor::RED_1)));
            }//test
            if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)) {
                $this->debugInterface->add(new PolyLine([$this->unit->position, new Vec2($this->unit->position->x + $this->targetVelocity->x + $vectorRecommend->x, $this->unit->position->y + $this->targetVelocity->y + $vectorRecommend->y)], 0.1, MyColor::getColor(MyColor::AQUA_1)));
            }//test

            $this->targetVelocity = new Vec2($this->targetVelocity->x + $vectorRecommend->x, $this->targetVelocity->y + $vectorRecommend->y);
        }
    }
}