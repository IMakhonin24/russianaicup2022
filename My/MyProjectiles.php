<?php

use Debugging\DebugData\PolyLine;
use Model\Game;
use Model\Projectile;
use Model\Vec2;

require_once 'CommonData.php';
require_once 'EveryTick.php';
require_once 'MyHistoryProjectiles.php';

class MyProjectiles implements CommonData, EveryTick
{
    private ?DebugInterface $debugInterface = null;
    private int $myPlayerId = 0;
    private int $currentTick = 0;

    /**
     * @var array | Projectile[]
     */
    public array $visibleProjectiles = [];

    /**
     * @var array | MyHistoryProjectiles[]
     */
    public array $historyProjectiles = [];

    /**
     * @var array | Projectile[]
     */
    public array $allProjectiles; //todo unset

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->allProjectiles = $game->projectiles;
        $this->myPlayerId = $game->myId;
        $this->currentTick = $game->currentTick;
        $this->debugInterface = $debugInterface;
    }

    public function everyTick(): void
    {
        $this->visibleProjectiles = [];

        $this->defineProjectilesMap();

    }

    private function defineProjectilesMap(): void
    {
        foreach ($this->allProjectiles as $projectile) {
            if ($projectile->shooterPlayerId != $this->myPlayerId) {
                $this->visibleProjectiles[$projectile->id] = $projectile;
            }

            $this->historyProjectiles[$projectile->id] = new MyHistoryProjectiles($projectile, $this->currentTick);
        }

        foreach ($this->historyProjectiles as $projectileId => $historyProjectile) {
            if ($historyProjectile->tick < $this->currentTick - MyCommonConst::CNT_TICK_SAVE_HISTORY_PROJECTILE) {
                unset($this->historyProjectiles[$projectileId]);
            } else {
                if (!isset($this->visibleProjectiles[$projectileId]) && !is_null($this->debugInterface)) {
                    $this->debugInterface->add(new PolyLine([
                        $historyProjectile->projectile->position,
                        new Vec2(
                            $historyProjectile->projectile->position->x + $historyProjectile->projectile->velocity->x,
                            $historyProjectile->projectile->position->y + $historyProjectile->projectile->velocity->y
                        )
                    ], 0.1, MyColor::getColor(MyColor::BLUE_05)));//Line to Velocity
                } //Рисуем примерную позицию истории юнита
            }
        }
    }

}