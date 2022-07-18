<?php

use Model\Vec2;

interface VelocityFilter
{
    public function getFilteredVelocity(Vec2 $targetVelocity): Vec2;
}