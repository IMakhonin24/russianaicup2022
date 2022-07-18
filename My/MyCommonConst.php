<?php

class MyCommonConst
{
    const GREEN_ZONE_RADIUS = 25; //Определяет зеленую зону
    const CNT_TICK_SAVE_SOUND = 20; //Сколько тиков будет храниться звук
    const CNT_TICK_SAVE_HISTORY_ENEMY = 70; //Сколько тиков будет храниться звук
    const CNT_TICK_SAVE_HISTORY_PROJECTILE = 10; //Сколько тиков будет храниться пуля
    const COEFFICIENT_PERSONAL_AREA = 40; //Определяет размер круга Personal Area


    const DANGER_LEVEL_HEALTH_PERCENT = 90; //Ниже какого процента здоровья определит высокий уровень тревоги
    const DANGER_LEVEL_SHIELD_PERCENT = 50; //Ниже какого процента брони определит высокий уровень тревоги
    const DANGER_DISTANCE_WEAPON_HIT = 6; // В каком радиусе проверяю наличие звуков попадания пуль max=40
    const DANGER_DISTANCE_WEAPON_SHOOT = 40; // В каком радиусе проверяю наличие звуков выстрелов max=30\40\20
    const DANGER_DISTANCE_STEP_SOUND = 10; // В каком радиусе проверяю наличие звуков выстрелов max=10
    const DANGER_UNIT_SAVE_PERSONAL_ZONE = 40; // В каком радиусе проверяю количество врагов вокруг
}