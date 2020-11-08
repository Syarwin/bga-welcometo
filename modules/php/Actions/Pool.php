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


  public function getScores($pId)
  {

  }
}
