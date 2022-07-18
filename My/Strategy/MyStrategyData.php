<?php

use Model\Constants;

class MyStrategyData
{
    public MyObstacles $myObstacles;
    public MyLoot $myLoot;
    public MyUnit $myUnit;
    public MyProjectiles $myProjectiles;
    public MySound $mySound;
    public Constants $constants;
    public ?DebugInterface $debugInterface;

    public function __construct(
        MyObstacles     $myObstacles,
        MyLoot          $myLoot,
        MyUnit          $myUnit,
        MyProjectiles   $myProjectiles,
        MySound         $mySound,
        Constants       $constants,
        ?DebugInterface $debugInterface
    )
    {
        $this->myObstacles = $myObstacles;
        $this->myLoot = $myLoot;
        $this->myUnit = $myUnit;
        $this->myProjectiles = $myProjectiles;
        $this->mySound = $mySound;
        $this->constants = $constants;
        $this->debugInterface = $debugInterface;
    }
}