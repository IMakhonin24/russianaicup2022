<?php

use Model\Game;

interface EveryTick
{
    public function everyTick(): void;
}