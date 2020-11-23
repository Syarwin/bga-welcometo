<?php
namespace WTO\Actions;

/*
 * Roundabout
 */
class Roundabout extends Zone
{
  protected static $type = "score-roundabout";
  protected static $dim = 1;
  protected static $cols = 2;

  protected static $scores = [0, 3, 8];
  public function getScore($player)
  {
    $free = count(self::$scores) - 1;
    foreach(self::getAvailableZones($player) as $zone)
      $free = $zone[0];

    return ['roundabout-total' => self::$scores[$free] ];
  }
}
