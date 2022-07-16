<?php

use Model\Constants;
use Model\Game;
use Model\Unit;
use Model\UnitOrder;
use Model\Vec2;

class MyOrder implements CommonData, EveryTick, EveryUnit
{
    private array $order = [];

    public function __construct(Constants $constants)
    {
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->loots = $game->loot;
    }

    public function everyTick(): void
    {
    }

    public function everyUnit(Unit $unit): void
    {
        $this->defineOrderForMyUnits($unit);
    }

    private function defineOrderForMyUnits(Unit $unit): void
    {
        $this->order[$unit->id] = new UnitOrder(
            new Vec2(-$unit->position->x, -$unit->position->y),
            new Vec2(-$unit->direction->y, $unit->direction->x),
            new Model\ActionOrder\Aim(true)
        );
    }

    public function getOrderForMyUnit(): array
    {
        return $this->order;
    }
}