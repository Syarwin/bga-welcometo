<?php
namespace WTO\Actions;
use \WTO\Helpers\Utils;

/*
 * IceCream : manage everything related to ice-creams
 */
class IceCream extends Zone
{
  protected static $type = "ice-cream";
  protected static $dim = 1;
  protected static $cols = 21;

  protected static $iceCreams = [
    [0,0], [0,2], [0,4], [0,5], [0,7], [0, 9],
    [1,0], [1,1], [1,3], [1,4], [1,6], [1, 8], [1,9],
    [2,0], [2,1], [2,3], [2,4], [2,5], [2, 7], [2,9], [2, 10]
  ];


  public function getConesToScribble($player)
  {
    $lastHouse = $player->getLastHouse();
    $cones = [];
    foreach(IceTruck::getHousesToCross($player) as $house){
      if(in_array([$house['x'], $house['y']], self::$iceCreams)){
        $house['state'] = ($house['x'] == $lastHouse['x'] && $house['y'] == $lastHouse['y'])? 1 : 0;
        $cones[] = $house;
      }
    }

    return $cones;
  }
}
