<?php

use Debugging\DebugData\Circle;
use Debugging\DebugData\PolyLine;
use Model\Constants;
use Model\Unit;
use Model\Vec2;

class ProjectileVelocityFilter implements VelocityFilter
{
    private const ENABLE_DEBUG_FILTER = false;

    private Vec2 $targetVelocity;

    private MyProjectiles $myProjectiles;
    private MyObstacles $myObstacles;

    private Unit $unit;

    private Constants $constants;

    private ?DebugInterface $debugInterface;

    public function __construct(Unit $unit, MyProjectiles $myProjectiles, MyObstacles $myObstacles, Constants $constants, ?DebugInterface $debugInterface)
    {
        $this->unit = $unit;
        $this->myProjectiles = $myProjectiles;
        $this->myObstacles = $myObstacles;
        $this->constants = $constants;
        $this->debugInterface = $debugInterface;
    }

    public function getFilteredVelocity(Vec2 $targetVelocity): Vec2
    {
        $this->targetVelocity = $targetVelocity;
        $this->applyProjectileFilter();
        return $this->targetVelocity;
    }

    private function applyProjectileFilter(): void
    {
        $ur11 = 0;
        $ur12 = 0;
        $rd11 = 0;
        $rd12 = 0;
        $dl11 = 0;
        $dl12 = 0;
        $lu11 = 0;
        $lu12 = 0;

        $userFictiveRadius = $this->constants->unitRadius * 2.5;
        //рисуем радиус проверки фильтра
        if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)){$this->debugInterface->add(new Circle($this->unit->position, $userFictiveRadius, MyColor::getColor(MyColor::BLUE_01)));}

        foreach ($this->myProjectiles->historyProjectiles as $historyProjectile) {
            $projectile = $historyProjectile->projectile;
            if (Helper::isIntersectionLineAndCircle(
                $projectile->position,
                new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y),
                $this->unit->position,
                $userFictiveRadius
            )) {
                if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, MyColor::getColor(MyColor::BLACK_02)));}

                $lineA = $projectile->position;
                $lineB = new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y);
                $perpendC = new Vec2($lineB->x - $lineA->x, $lineB->y - $lineA->y);
                $perpendD = Helper::getPerpendicularTo(
                    $lineA,
                    $lineB,
                    $perpendC
                );
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
                if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$projectile->position, new Vec2($projectile->position->x + $projectile->velocity->x, $projectile->position->y + $projectile->velocity->y)], 0.1, MyColor::getColor(MyColor::BLACK_01)));}
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
            $nextUnitPosition1 = new Vec2($this->unit->position->x + 4, $this->unit->position->y + 10);
            $nextUnitPosition2 = new Vec2($this->unit->position->x - 4, $this->unit->position->y - 10);
        } elseif ($priorityDirection == "UR2" || $priorityDirection == "DL2") {
            $nextUnitPosition1 = new Vec2($this->unit->position->x + 10, $this->unit->position->y + 4);
            $nextUnitPosition2 = new Vec2($this->unit->position->x - 10, $this->unit->position->y - 4);
        } elseif ($priorityDirection == "RD1" || $priorityDirection == "LU1") {
            $nextUnitPosition1 = new Vec2($this->unit->position->x + 10, $this->unit->position->y - 4);
            $nextUnitPosition2 = new Vec2($this->unit->position->x - 10, $this->unit->position->y + 4);
        } elseif ($priorityDirection == "RD2" || $priorityDirection == "LU2") {
            $nextUnitPosition1 = new Vec2($this->unit->position->x + 4, $this->unit->position->y - 10);
            $nextUnitPosition2 = new Vec2($this->unit->position->x - 4, $this->unit->position->y + 10);
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
            if (self::ENABLE_DEBUG_FILTER && !is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$this->unit->position, $position], 0.1, MyColor::getColor(MyColor::RED_1)));}

            $vectorRecommend = Helper::getVectorAB($this->unit->position, $position);
            $this->targetVelocity = new Vec2($this->targetVelocity->x + $vectorRecommend->x, $this->targetVelocity->y + $vectorRecommend->y);
        }
    }
}