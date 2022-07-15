<?php

use Model\Game;

interface CommonData
{
    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void;
}