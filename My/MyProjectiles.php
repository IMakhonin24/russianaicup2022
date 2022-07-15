<?php

use Model\Game;
use Model\Projectile;

require_once 'CommonData.php';
require_once 'EveryTick.php';

class MyProjectiles implements CommonData, EveryTick
{
    private int $myPlayerId = 0;

    /**
     * @var array | Projectile[]
     */
    public array $projectiles; //todo unset

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->projectiles = $game->projectiles;
        $this->myPlayerId = $game->myId;
    }

    public function everyTick(): void
    {
        $this->defineProjectilesMap();

    }

    private function defineProjectilesMap(): void
    {
        $this->projectiles = [];

        /** @var Projectile[] $projectiles */
        $projectiles = $this->projectiles;
        foreach ($projectiles as $projectile) {
            if ($projectile->shooterPlayerId != $this->myPlayerId) {
                $this->projectiles[$projectile->id] = $projectile;
            }
        }
    }

}