<?php
namespace WTO\Actions;
use WTO\Helpers\Utils;

/*
 * EasterEgg : manage everything related to easter-eggs
 */
class EasterEgg extends Zone
{
  protected static $type = 'egg';
  protected static $cols = [10, 11, 12];

  protected static $eggs = [
    [0, 2, 1],
    [0, 3, 2],
    [0, 4, 1],
    [0, 6, 1],
    [0, 9, 1],

    [1, 0, 2],
    [1, 3, 1],
    [1, 5, 1],
    [1, 6, 2],
    [1, 8, 1],

    [2, 2, 1],
    [2, 4, 2],
    [2, 5, 1],
    [2, 7, 2],
    [2, 9, 1],
  ];

  public function getEggsToScribble($player)
  {
    $house = $player->getLastHouse();
    $eggs = [];
    foreach (static::$eggs as $egg) {
      if ($egg[0] == $house['x'] && $egg[1] == $house['y']) {
        $n = 0;
        if ($egg[2] == 2 && in_array($house['number'], [0, 8, 10])) {
          $n = 2;
        } elseif (in_array($house['number'], [0, 8, 10, 6, 9, 16])) {
          $n = 1;
        }

        if ($n > 0) {
          $eggs[] = [
            'x' => $house['x'],
            'y' => $house['y'],
            'state' => $n,
          ];
        }
      }
    }

    return $eggs;
  }

  public function getScore($player)
  {
    $res = [];
    $nEggs = 0;
    foreach (self::getOfPlayer($player) as $scribble) {
      $nEggs += $scribble['state'];
    }

    $score = 0;
    if ($nEggs >= 18) {
      $score = 35;
    } elseif ($nEggs >= 14) {
      $score = 20;
    } elseif ($nEggs >= 10) {
      $score = 10;
    } elseif ($nEggs >= 6) {
      $score = 5;
    }

    $res['easter-egg-total'] = $score;
    return $res;
  }
}
