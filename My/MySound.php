<?php

use Debugging\DebugData\Circle;
use Debugging\DebugData\PlacedText;
use Model\Constants;
use Model\Game;
use Model\Sound;
use Model\Vec2;

require_once 'MyHistorySound.php';

class MySound implements CommonData, EveryTick
{
    const STEPS = 0;        //Оранжевый
    const PISTOL_SOOT = 1;  //Зеленый
    const GUN_SOOT = 2;     //Синий
    const SNIPER_SOOT = 3;  //Красный
    const PISTOL_HIT = 4;   //Зеленый
    const GUN_HIT = 5;      //Синий
    const SNIPER_HIT = 6;   //Красный

    private float $unitRadius;

    private ?DebugInterface $debugInterface = null;
    private int $currentTick = 0;

    /**
     * Массив звуков шагов
     *
     * @var array | MyHistorySound[]
     */
    public array $soundsSteps = [];

    /**
     * Массив звуков выстрел пистолета
     *
     * @var array | MyHistorySound[]
     */
    public array $soundsPistolShoot = [];

    /**
     * Массив звуков выстрел автомата
     *
     * @var array | MyHistorySound[]
     */
    public array $soundsGunShoot = [];

    /**
     * Массив звуков выстрел снайперки
     *
     * @var array | MyHistorySound[]
     */
    public array $soundsSniperShoot = [];

    /**
     * Массив звуков попадание пистолета
     *
     * @var array | MyHistorySound[]
     */
    public array $soundsPistolHit = [];

    /**
     * Массив звуков попадание автомата
     *
     * @var array | MySound[]
     */
    public array $soundsGunHit = [];

    /**
     * Массив звуков попадание снайперки
     *
     * @var array | MySound[]
     */
    public array $soundsSniperHit = [];

    /**
     * @var array | Sound[]
     */
    private array $sounds; //todo del

    public function __construct(Constants $constants)
    {
        $this->unitRadius = $constants->unitRadius;
    }

    public function setCommonData(Game $game, ?DebugInterface $debugInterface): void
    {
        $this->sounds = $game->sounds;
        $this->currentTick = $game->currentTick;
        $this->debugInterface = $debugInterface;
    }

    public function everyTick(): void
    {
        $this->defineSoundsMap();
    }

    private function defineSoundsMap(): void
    {
        foreach ($this->sounds as $sound) {
            switch ($sound->typeIndex) {
                case self::STEPS:
                    $this->soundsSteps[] = new MyHistorySound($sound, $this->currentTick);;
                    break;
                case self::PISTOL_SOOT:
                    $this->soundsPistolShoot[] = new MyHistorySound($sound, $this->currentTick);
                    break;
                case self::PISTOL_HIT:
                    $this->soundsPistolHit[] = new MyHistorySound($sound, $this->currentTick);
                    break;
                case self::GUN_SOOT:
                    $this->soundsGunShoot[] = new MyHistorySound($sound, $this->currentTick);
                    break;
                case self::GUN_HIT:
                    $this->soundsGunHit[] = new MyHistorySound($sound, $this->currentTick);
                    break;
                case self::SNIPER_SOOT:
                    $this->soundsSniperShoot[] = new MyHistorySound($sound, $this->currentTick);
                    break;
                case self::SNIPER_HIT:
                    $this->soundsSniperHit[] = new MyHistorySound($sound, $this->currentTick);
                    break;
            }
        }

        //удаляем устаревшие звуки
        foreach ($this->soundsSteps as $soundIndex => $mySound) {
            if ($mySound->tick < $this->currentTick - MyCommonConst::CNT_TICK_SAVE_SOUND) {
                unset($this->soundsSteps[$soundIndex]);
                $this->soundsSteps = array_values($this->soundsSteps);
            } else {
                if (!is_null($this->debugInterface)) {
                    $this->debugInterface->add(new Circle($mySound->sound->position, $this->unitRadius, MyColor::getColor(MyColor::ORANGE_01)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.35, $mySound->sound->position->y+0.1), "Sound", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.3, $mySound->sound->position->y-0.1), "Steps", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                } //Рисуем примерную позицию звука
            }
        }
        foreach ($this->soundsPistolShoot as $soundIndex => $mySound) {
            if ($mySound->tick < $this->currentTick - MyCommonConst::CNT_TICK_SAVE_SOUND) {
                unset($this->soundsPistolShoot[$soundIndex]);
                $this->soundsPistolShoot = array_values($this->soundsPistolShoot);
            } else {
                if (!is_null($this->debugInterface)) {
                    $this->debugInterface->add(new Circle($mySound->sound->position, $this->unitRadius, MyColor::getColor(MyColor::LIGHT_GREEN_01)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.35, $mySound->sound->position->y+0.1), "Sound", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.5, $mySound->sound->position->y-0.1), "PistolShot", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));

                } //Рисуем примерную позицию звука
            }
        }
        foreach ($this->soundsPistolHit as $soundIndex => $mySound) {
            if ($mySound->tick < $this->currentTick - MyCommonConst::CNT_TICK_SAVE_SOUND) {
                unset($this->soundsPistolHit[$soundIndex]);
                $this->soundsPistolHit = array_values($this->soundsPistolHit);
            } else {
                if (!is_null($this->debugInterface)) {
                    $this->debugInterface->add(new Circle($mySound->sound->position, $this->unitRadius, MyColor::getColor(MyColor::LIGHT_GREEN_01)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.35, $mySound->sound->position->y+0.1), "Sound", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.4, $mySound->sound->position->y-0.1), "PistolHit", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));

                } //Рисуем примерную позицию звука
            }
        }
        foreach ($this->soundsGunShoot as $soundIndex => $mySound) {
            if ($mySound->tick < $this->currentTick - MyCommonConst::CNT_TICK_SAVE_SOUND) {
                unset($this->soundsGunShoot[$soundIndex]);
                $this->soundsGunShoot = array_values($this->soundsGunShoot);
            } else {
                if (!is_null($this->debugInterface)) {
                    $this->debugInterface->add(new Circle($mySound->sound->position, $this->unitRadius, MyColor::getColor(MyColor::LIGHT_BLUE_01)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.35, $mySound->sound->position->y+0.1), "Sound", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.35, $mySound->sound->position->y-0.1), "GunShot", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));

                } //Рисуем примерную позицию звука
            }
        }
        foreach ($this->soundsGunHit as $soundIndex => $mySound) {
            if ($mySound->tick < $this->currentTick - MyCommonConst::CNT_TICK_SAVE_SOUND) {
                unset($this->soundsGunHit[$soundIndex]);
                $this->soundsGunHit = array_values($this->soundsGunHit);
            } else {
                if (!is_null($this->debugInterface)) {
                    $this->debugInterface->add(new Circle($mySound->sound->position, $this->unitRadius, MyColor::getColor(MyColor::LIGHT_BLUE_01)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.35, $mySound->sound->position->y+0.1), "Sound", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.35, $mySound->sound->position->y-0.1), "GunHit", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));

                } //Рисуем примерную позицию звука
            }
        }
        foreach ($this->soundsSniperShoot as $soundIndex => $mySound) {
            if ($mySound->tick < $this->currentTick - MyCommonConst::CNT_TICK_SAVE_SOUND) {
                unset($this->soundsSniperShoot[$soundIndex]);
                $this->soundsSniperShoot = array_values($this->soundsSniperShoot);
            } else {
                if (!is_null($this->debugInterface)) {
                    $this->debugInterface->add(new Circle($mySound->sound->position, $this->unitRadius, MyColor::getColor(MyColor::LIGHT_RED_01)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.35, $mySound->sound->position->y+0.1), "Sound", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.4, $mySound->sound->position->y-0.1), "SniperShot", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));

                } //Рисуем примерную позицию звука
            }
        }
        foreach ($this->soundsSniperHit as $soundIndex => $mySound) {
            if ($mySound->tick < $this->currentTick - MyCommonConst::CNT_TICK_SAVE_SOUND) {
                unset($this->soundsSniperHit[$soundIndex]);
                $this->soundsSniperHit = array_values($this->soundsSniperHit);
            } else {
                if (!is_null($this->debugInterface)) {
                    $this->debugInterface->add(new Circle($mySound->sound->position, $this->unitRadius, MyColor::getColor(MyColor::LIGHT_RED_01)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.35, $mySound->sound->position->y+0.1), "Sound", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                    $this->debugInterface->add(new PlacedText(new Vec2($mySound->sound->position->x-0.4, $mySound->sound->position->y-0.1), "SniperHit", new Vec2(0, 0), 0.2, MyColor::getColor(MyColor::BLACK_1)));
                } //Рисуем примерную позицию звука
            }
        }
    }
}