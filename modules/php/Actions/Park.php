<?php
namespace WTO\Actions;
use \WTO\Helpers\Utils;

/*
 * Park : manage everything related to park
 */
class Park extends Zone
{
  protected static $type = "park";
  protected static $cols = [3,4,5];

  public function getAvailableZones($player)
  {
    // Keep only the park on the same street as the house we just built
    $zones = parent::getAvailableZones($player);
    $house = $player->getLastHouse();
    Utils::filter($zones, function($zone) use ($house){
      return $zone[0] == $house['x'];
    });

    return $zones;
  }


  protected static $scores = [
    [0, 2, 4, 10],
    [0, 2, 4, 6, 14],
    [0, 2, 4, 6, 8, 18],
  ];
  public function getScore($player)
  {
    $free = [3, 4, 5];
    foreach(parent::getAvailableZones($player) as $zone)
      $free[$zone[0]] = $zone[1];

    $res = [
      'park-0' => self::$scores[0][$free[0]],
      'park-1' => self::$scores[1][$free[1]],
      'park-2' => self::$scores[2][$free[2]],
    ];
    $res['park-total'] = $res['park-0'] + $res['park-1'] + $res['park-2'];

    return $res;
  }
}
