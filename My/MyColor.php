<?php

use Debugging\Color;

class MyColor
{
    const LIGHT_BLUE_01 = 100;

    const BLUE_01 = 150;
    const BLUE_05 = 160;
    const BLUE_1 = 170;

    const BLACK_01 = 200;
    const BLACK_02 = 210;
    const BLACK_05 = 220;
    const BLACK_1 = 230;

    const LIGHT_GREEN_01 = 250;

    const GREEN_01 = 300;
    const GREEN_02 = 310;
    const GREEN_05 = 320;
    const GREEN_1 = 330;

    const WHITE_01 = 350;
    const WHITE_05 = 360;
    const WHITE_1 = 370;

    const LIGHT_RED_01 = 400;

    const RED_01 = 450;
    const RED_05 = 460;
    const RED_1 = 470;

    const AQUA_01 = 500;
    const AQUA_05 = 510;
    const AQUA_1 = 520;

    const ORANGE_01 = 550;
    const ORANGE_05 = 560;
    const ORANGE_1 = 570;

    const VIOLET_01 = 600;
    const VIOLET_05 = 610;
    const VIOLET_1 = 620;

    const YELLOW_01 = 650;
    const YELLOW_05 = 660;
    const YELLOW_1 = 670;

    public static function getColor(int $colorId): Color
    {
        switch ($colorId) {
            case self::LIGHT_BLUE_01 :
                return new Color(0, 0, 50, 0.1);

            case self::BLUE_01 :
                return new Color(0, 0, 255, 0.1);
            case self::BLUE_05 :
                return new Color(0, 0, 255, 0.5);
            case self::BLUE_1 :
                return new Color(0, 0, 255, 1);

            case self::BLACK_01 :
                return new Color(0, 0, 0, 0.1);
            case self::BLACK_02 :
                return new Color(0, 0, 0, 0.2);
            case self::BLACK_05 :
                return new Color(0, 0, 0, 0.5);
            case self::BLACK_1 :
                return new Color(0, 0, 0, 1);

            case self::LIGHT_GREEN_01 :
                return new Color(0, 50, 0, 0.1);

            case self::GREEN_01 :
                return new Color(0, 255, 0, 0.1);
            case self::GREEN_02 :
                return new Color(0, 255, 0, 0.2);
            case self::GREEN_05 :
                return new Color(0, 255, 0, 0.5);
            case self::GREEN_1 :
                return new Color(0, 255, 0, 1);

            case self::WHITE_01 :
                return new Color(255, 255, 255, 0.1);
            case self::WHITE_05 :
                return new Color(255, 255, 255, 0.5);
            case self::WHITE_1 :
                return new Color(255, 255, 255, 1);

            case self::LIGHT_RED_01 :
                return new Color(50, 0, 0, 0.1);

            case self::RED_01 :
                return new Color(255, 0, 0, 0.1);
            case self::RED_05 :
                return new Color(255, 0, 0, 0.5);
            case self::RED_1 :
                return new Color(255, 0, 0, 1);

            case self::AQUA_01 :
                return new Color(0, 255, 255, 0.1);
            case self::AQUA_05 :
                return new Color(0, 255, 255, 0.5);
            case self::AQUA_1 :
                return new Color(0, 255, 255, 1);

            case self::ORANGE_01 :
                return new Color(255, 102, 0, 0.1);
            case self::ORANGE_05 :
                return new Color(255, 102, 0, 0.5);
            case self::ORANGE_1 :
                return new Color(255, 102, 0, 1);

            case self::VIOLET_01 :
                return new Color(128, 0, 255, 0.1);
            case self::VIOLET_05 :
                return new Color(128, 0, 255, 0.5);
            case self::VIOLET_1 :
                return new Color(128, 0, 255, 1);

            case self::YELLOW_01 :
                return new Color(255, 255, 0, 0.1);
            case self::YELLOW_05 :
                return new Color(255, 255, 0, 0.5);
            case self::YELLOW_1 :
                return new Color(255, 255, 0, 1);
        }
    }
}