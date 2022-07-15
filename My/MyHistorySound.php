<?php

use Model\Sound;

class MyHistorySound{

    public Sound $sound;
    public int $tick;

    public function __construct(Sound $sound, $tick)
    {
        $this->sound = $sound;
        $this->tick = $tick;
    }
}