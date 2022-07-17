<?php

use Model\ActionOrder;

class MyActionOrder
{
    private ?ActionOrder $actionOrder = null;

    public function __construct()
    {
    }

    public function setActionOrder(?ActionOrder $actionOrder): void
    {
        $this->actionOrder = $actionOrder;
    }

    public function getActionOrder(): ?ActionOrder
    {
        return $this->actionOrder;
    }

}