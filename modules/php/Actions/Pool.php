<?php
namespace WTO\Actions;

/*
 * Bis : manage everything related to bis
 */
class Pool extends Zone
{
  protected static $type = "score-pool";
  protected static $dim = 1;
  protected static $cols = 9;

  protected static $pools = [
    [0,2], [0,6], [0,7],
    [1,0], [1,3], [1,7],
    [2,1], [2,6], [2,10],
  ];

  public function canBuild($player)
  {
    $house = $player->getLastHouse();
    $pos = [$house['x'], $house['y']];
    return in_array($pos, static::$pools);
  }


  protected static $scores = [0, 3, 6, 9, 13, 17, 21, 26, 31, 36];
  public function getScore($player)
  {
    $free = count(self::$scores) - 1;
    foreach(self::getAvailableZones($player) as $zone)
      $free = $zone[0];

    return ['pool-total' => self::$scores[$free] ];
  }
}
