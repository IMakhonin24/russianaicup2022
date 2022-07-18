<?php

use Model\Vec2;

class Helper
{
    /**
     * Вернет дистанцию между точками $a и $b
     *
     * @param Vec2 $a
     * @param Vec2 $b
     * @return float
     */
    public static function getDistance(Vec2 $a, Vec2 $b): float
    {
        return sqrt(($a->x - $b->x) * ($a->x - $b->x) + ($a->y - $b->y) * ($a->y - $b->y));
    }

    /**
     * Вернет вектор от $a к $b.
     * Точка относительно (0,0)
     *
     * @param Vec2 $a
     * @param Vec2 $b
     * @return Vec2
     */
    public static function getVectorAB(Vec2 $a, Vec2 $b): Vec2
    {
        return new Vec2($b->x - $a->x, $b->y - $a->y);
    }

    /**
     * Проверит, входит ли точка в заданную окружность
     *
     * @param Vec2 $centreCircle
     * @param float $radius
     * @param Vec2 $checkPoint
     * @return bool
     */
    public static function isPointInCircle(Vec2 $centreCircle, float $radius, Vec2 $checkPoint): bool
    {
        $x = $checkPoint->x - $centreCircle->x;
        $y = $checkPoint->y - $centreCircle->y;
        $hypotenuse = sqrt(($x * $x) + ($y * $y));
        if ($hypotenuse <= $radius) {
            return true;
        }
        return false;
    }

    /**
     * Проверит, пересекает ли прямая $x1$x2 окружность
     *
     * вернет true в случаях если отрезок рассматривать как бесконечную прямую
     *
     * @param Vec2 $x1
     * @param Vec2 $x2
     * @param Vec2 $circleCentre
     * @param float $radius
     * @return bool
     */
    public static function isIntersectionLineAndCircle2(Vec2 $x1, Vec2 $x2, Vec2 $circleCentre, float $radius): bool
    {
        $dx01 = $x1->x - $circleCentre->x;
        $dy01 = $x1->y - $circleCentre->y;
        $dx12 = $x2->x - $x1->x;
        $dy12 = $x2->y - $x1->y;

        $a = pow($dx12, 2) + pow($dy12, 2);

        if (abs($a) < PHP_FLOAT_EPSILON) {
            return false;//Начало и конец отрезка совпадают
        }

        $k = $dx01 * $dx12 + $dy01 * $dy12;
        $c = pow($dx01, 2) + pow($dy01, 2) - pow($radius, 2);
        $d1 = pow($k, 2) - $a * $c;

        if ($d1 < 0) {
            return false; //Отрезок не пересекается с окружностью - отрезок снаружи
        } else if (abs($d1) < PHP_FLOAT_EPSILON) {
            return false; //Прямая касается окружности в точке
        } else {
            return true; //Прямая пересекает окружность в двух точках
        }
    }

    /**
     * Проверит пересечение отрезков AB и CD
     *
     * @param Vec2 $pointA
     * @param Vec2 $pointB
     * @param Vec2 $pointC
     * @param Vec2 $pointD
     * @return bool
     */
    public static function isIntersectionTwoLine(Vec2 $pointA, Vec2 $pointB, Vec2 $pointC, Vec2 $pointD): bool
    {
        $v1 = ($pointD->x - $pointC->x) * ($pointA->y - $pointC->y) - ($pointD->y - $pointC->y) * ($pointA->x - $pointC->x);
        $v2 = ($pointD->x - $pointC->x) * ($pointB->y - $pointC->y) - ($pointD->y - $pointC->y) * ($pointB->x - $pointC->x);
        $v3 = ($pointB->x - $pointA->x) * ($pointC->y - $pointA->y) - ($pointB->y - $pointA->y) * ($pointC->x - $pointA->x);
        $v4 = ($pointB->x - $pointA->x) * ($pointD->y - $pointA->y) - ($pointB->y - $pointA->y) * ($pointD->x - $pointA->x);

        if (($v1 * $v2 < 0) && ($v3 * $v4 < 0)) {
            return true;
        }
        return false;
    }

    /**
     * Вернет точку на отрезке AB, через которую можно построить перпендикуляр от точки C к отрезку AB
     *
     * @param Vec2 $lineA
     * @param Vec2 $lineB
     * @param Vec2 $pointC
     * @return Vec2
     */
    public static function getPerpendicularTo(Vec2 $lineA, Vec2 $lineB, Vec2 $pointC): Vec2
    {
        $x1 = $lineA->x;
        $y1 = $lineA->y;
        $x2 = $lineB->x;
        $y2 = $lineB->y;
        $x3 = $pointC->x;
        $y3 = $pointC->y;

        $x = ($x1 * $x1 * $x3 - 2 * $x1 * $x2 * $x3 + $x2 * $x2 * $x3 + $x2 * ($y1 - $y2) * ($y1 - $y3) - $x1 * ($y1 - $y2) * ($y2 - $y3)) / (($x1 - $x2) * ($x1 - $x2) + ($y1 - $y2) * ($y1 - $y2));
        $y = ($x2 * $x2 * $y1 + $x1 * $x1 * $y2 + $x2 * $x3 * ($y2 - $y1) - $x1 * ($x3 * ($y2 - $y1) + $x2 * ($y1 + $y2)) + ($y1 - $y2) * ($y1 - $y2) * $y3) / (($x1 - $x2) * ($x1 - $x2) + ($y1 - $y2) * ($y1 - $y2));

        return new Vec2($x, $y);

    }

    /**
     * Построит средний обратный вектор относительно массива точек.
     *
     * todo вернуть нормальный вектор. Сделать функцию которая возвращает обратный вектор
     *
     * @param Vec2 $centre
     * @param array | Vec2[] $points
     * @return Vec2
     */
    public static function getAverageVectorFromOneCentre(Vec2 $centre, array $points): Vec2
    {
        $averageX = 0;
        $averageY = 0;
        foreach ($points as $point) {
            $averageX = $averageX + ($point->x - $centre->x);
            $averageY = $averageY + ($point->y - $centre->y);
        }

        return new Vec2($centre->x + ($averageX * -1), $centre->y + ($averageY * -1));
    }

    /**
     * Вернет точку C которая лежит на прямой AB на расстоянии AC
     *
     * @param Vec2 $lineA
     * @param Vec2 $lineB
     * @param float $distanceAC
     * @return Vec2
     */
    public static function getPointOnLineABAtDistanceAC(Vec2 $lineA, Vec2 $lineB, float $distanceAC): Vec2
    {
        $distanceAB = Helper::getDistance($lineA, $lineB);
        $k = $distanceAC / $distanceAB;
        return new Vec2($lineA->x + ($lineB->x - $lineA->x) * $k, $lineA->y + ($lineB->y - $lineA->y) * $k);
    }

    /**
     * Проверит, пересекает ли отрезок $x1$x2 окружность
     *
     * @param Vec2 $x1
     * @param Vec2 $x2
     * @param Vec2 $circleCentre
     * @param float $radius
     * @return bool
     */
    public static function isIntersectionLineAndCircle(Vec2 $x1, Vec2 $x2, Vec2 $circleCentre, float $radius): bool
    {
        $x01 = $x1->x - $circleCentre->x;
        $y01 = $x1->y - $circleCentre->y;
        $x02 = $x2->x - $circleCentre->x;
        $y02 = $x2->y - $circleCentre->y;

        $dx = $x02 - $x01;
        $dy = $y02 - $y01;

        $a = $dx * $dx + $dy * $dy;
        $b = 2 * ($x01 * $dx + $y01 * $dy);
        $c = $x01 * $x01 + $y01 * $y01 - $radius * $radius;

        if (-$b < 0) return ($c < 0);
        if (-$b < (2 * $a)) return (4 * $a * $c - $b * $b < 0);

        return ($a + $b + $c < 0);
    }

}