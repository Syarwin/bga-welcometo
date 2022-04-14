<?php
namespace WTO\Actions;
use WTO\Helpers\Utils;
use WTO\Houses;

/*
 * Christmas : manage everything related to christmas
 */
class Christmas extends Zone
{
  protected static $type = 'christmas';
  protected static $cols = [9, 10, 11];

  public function getScore($player)
  {
    $christmas = self::getOfPlayerStructured($player);
    $res = [
      'christmas-0' => 0,
      'christmas-1' => 0,
      'christmas-2' => 0,
      'christmas-total' => 0,
    ];

    for ($i = 0; $i < 3; $i++) {
      $max = 0;
      $current = 0;
      for ($j = 0; $j < self::$cols[$i]; $j++) {
        if (is_null($christmas[$i][$j])) {
          $current = 0;
        } else {
          $current++;
          $max = max($max, $current);
        }
      }

      if ($max > 0) {
        $max++;
      }
      $res['christmas-' . $i] = $max;
      $res['christmas-total'] += $max;
    }

    return $res;
  }

  public function getChristmasToScribble($player)
  {
    $christmas = self::getOfPlayerStructured($player);
    $streets = Houses::getStreets($player->getId());
    $lastHouse = $player->getLastHouse();

    $houses = [];
    if ($lastHouse['y'] < self::$cols[$lastHouse['x']]) {
      $houses[] = [
        'x' => $lastHouse['x'],
        'y' => $lastHouse['y'],
      ];
    }
    if ($lastHouse['y'] > 0) {
      $houses[] = [
        'x' => $lastHouse['x'],
        'y' => $lastHouse['y'] - 1,
      ];
    }

    $zones = [];
    foreach ($houses as $h) {
      if (!is_null($christmas[$h['x']][$h['y']])) {
        continue;
      }

      $h1 = $streets[$h['x']][$h['y']];
      $h2 = $streets[$h['x']][$h['y'] + 1];
      if (
        !is_null($h1) &&
        !is_null($h2) &&
        $h1['number'] != \ROUNDABOUT &&
        $h2['number'] != \ROUNDABOUT &&
        $h2['number'] <= $h1['number'] + 1
      ) {
        $zones[] = $h;
      }
    }

    return $zones;
  }
}