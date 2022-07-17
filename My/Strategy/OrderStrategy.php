<?php

use Model\UnitOrder;

interface OrderStrategy
{
    public function getOrder(): UnitOrder;
}