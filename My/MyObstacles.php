<?php

use Debugging\DebugData\Circle;
use Debugging\DebugData\PolyLine;
use Model\Constants;
use Model\Game;
use Model\Obstacle;
use Model\Unit;
use Model\Vec2;

class MyObstacles
{
    private Constants $constants;

    /**
     * @var array | Obstacle[]
     */
    public array $obstacles; //Массив препятствий

    private ?DebugInterface $debugInterface;

    public function __construct(Constants $constants)
    {
        $this->constants = $constants;
        $this->defineObstaclesMap($constants);
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->debugInterface = $debugInterface;
    }

    private function defineObstaclesMap(Constants $constants)
    {
        $this->obstacles = [];
        foreach ($constants->obstacles as $obstacle) {
            $this->obstacles[] = $obstacle;
        }
    }

    public function defineNearestObstaclesMyUnit(Unit $unit): void
    {
        $checkObstacleUnitRadius = 10;

        if (!is_null($this->debugInterface)){$this->debugInterface->add(new Circle($unit->position, $checkObstacleUnitRadius, MyColor::getColor(MyColor::BLACK_01)));}

        //todo парсинг препятствий
        foreach ($this->obstacles as $obstacle) {
            if (Helper::isPointInCircle($unit->position, $checkObstacleUnitRadius, $obstacle->position)){
                if ($unit->velocity->x != 0 || $unit->velocity->y != 0){
                    $perpendicular = Helper::getPerpendicularTo($unit->position, new Vec2($unit->position->x + $unit->velocity->x, $unit->position->y + $unit->velocity->y), $obstacle->position);
                    $distanceFromObstacleToPerpendicular = Helper::getDistance($obstacle->position, $perpendicular);
                    if ($distanceFromObstacleToPerpendicular < $obstacle->radius + $this->constants->unitRadius) {
//                        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$obstacle->position, $perpendicular], 0.1, MyColor::getColor(MyColor::RED_1)));}
//                        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($obstacle->position->x+0.3, $obstacle->position->y+0.2), "ObtR=".$obstacle->radius, new Vec2(0, 0), 0.3, MyColor::getColor(MyColor::GREEN_1)));}
//                        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PlacedText(new Vec2($obstacle->position->x+0.3, $obstacle->position->y+1), $distanceFromObstacleToPerpendicular, new Vec2(0, 0), 0.3, MyColor::getColor(MyColor::RED_1)));}
//                        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$obstacle->position, new Vec2($obstacle->position->x, $obstacle->position->y + $obstacle->radius)], 0.1, MyColor::getColor(MyColor::GREEN_1)));}//test
//                        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$obstacle->position, new Vec2($obstacle->position->x, $obstacle->position->y - $obstacle->radius)], 0.1, MyColor::getColor(MyColor::GREEN_1)));}//test
//                        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$obstacle->position, new Vec2($obstacle->position->x + $obstacle->radius, $obstacle->position->y)], 0.1, MyColor::getColor(MyColor::GREEN_1)));}//test
//                        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$obstacle->position, new Vec2($obstacle->position->x - $obstacle->radius, $obstacle->position->y)], 0.1, MyColor::getColor(MyColor::GREEN_1)));}//test
//                        if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$obstacle->position, $perpendicular], 0.1, MyColor::getColor(MyColor::RED_1)));}
                    }
                }
                if (!is_null($this->debugInterface)){$this->debugInterface->add(new PolyLine([$unit->position, new Vec2($unit->position->x + $unit->velocity->x, $unit->position->y + $unit->velocity->y)], 0.3, MyColor::getColor(MyColor::YELLOW_1)));}
            }
        }
    }
}