<?php

use Model\Projectile;

class MyHistoryProjectiles
{

    public Projectile $projectile;
    public int $tick;

    public function __construct(Projectile $projectile, $tick)
    {
        $this->projectile = $projectile;
        $this->tick = $tick;
    }
}