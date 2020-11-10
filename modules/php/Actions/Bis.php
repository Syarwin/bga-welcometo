<?php
namespace WTO\Actions;

/*
 * Bis : manage everything related to bis
 */
class Bis extends Zone
{
  protected static $type = "score-bis";
  protected static $dim = 1;
  protected static $cols = 9;

  protected static $scores = [0, 1, 3, 6, 9, 12, 16, 20, 24, 28];
  public function getScore($player)
  {
    $free = count(self::$scores) - 1;
    foreach(self::getAvailableZones($player) as $zone)
      $free = $zone[0];

    return ['bis-total' => self::$scores[$free] ];
  }
}
