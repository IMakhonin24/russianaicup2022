<?php

use Model\Constants;
use Model\Game;
use Model\Item\Ammo;
use Model\Item\ShieldPotions;
use Model\Item\Weapon;
use Model\Loot;
use Model\Unit;

require_once 'CommonData.php';
require_once 'EveryTick.php';
require_once 'EveryUnit.php';
require_once 'MyCommonConst.php';
require_once 'Loot/MyWeapon.php';
require_once 'Loot/MyPot.php';
require_once 'Loot/MyAmmo.php';

class MyLoot implements CommonData, EveryTick, EveryUnit
{
    public MyWeapon $myWeapon;
    public MyPot $myPot;
    public MyAmmo $myAmmo;

    /**
     * @var array | Loot[]
     */
    private array $loots; //todo unset

    public function __construct(Constants $constants)
    {
        $this->myWeapon = new MyWeapon();
        $this->myPot = new MyPot();
        $this->myAmmo = new MyAmmo();
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->loots = $game->loot;
    }

    public function everyTick(): void
    {
        $this->defineLootMap();

        $this->myWeapon->everyTick();
        $this->myPot->everyTick();
        $this->myAmmo->everyTick();
    }

    public function everyUnit(Unit $unit): void
    {
        $this->myWeapon->everyUnit($unit);
        $this->myPot->everyUnit($unit);
        $this->myAmmo->everyUnit($unit);
    }

    private function defineLootMap(): void
    {
        foreach ($this->loots as $loot) {
            $lootClass = get_class($loot->item);
            switch ($lootClass) {
                case Weapon::class:
                    $this->myWeapon->setWeapon($loot);
                    break;
                case ShieldPotions::class:
                    $this->myPot->setPot($loot);
                    break;
                case Ammo::class:
                    $this->myAmmo->setAmmo($loot);
                    break;
            }
        }
    }

}