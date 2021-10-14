<?php
namespace WTO\Actions;
use WTO\Houses;

/*
 * IceTruck : manage marking related to ice trucks
 */
class IceTruck extends Zone
{
  protected static $type = 'ice-truck';
  protected static $cols = [10, 11, 12];

  public function getTruckPositions($player)
  {
    $structure = self::getOfPlayerStructured($player);
    $positions = [];

    // First line : right to left
    for ($i = 9; $i > 0 && !is_null($structure[0][$i]); $i--);
    $positions[0] = $i + 1;

    // Second line : left to right
    for ($i = 0; $i < 11 && !is_null($structure[1][$i]); $i++);
    $positions[1] = $i - 1;

    // Third line : need to choose
    // TODO
    $positions[2] = null;

    return $positions;
  }

  public function getHousesToCross($player)
  {
    $structure = self::getOfPlayerStructured($player);
    $house = $player->getLastHouse();

    $houses = [];
    if (is_null($structure[$house['x']][$house['y']])) {
      $truckPosition = self::getTruckPositions($player)[$house['x']];
      for ($i = 0; $i < self::$cols[$house['x']]; $i++) {
        if (($i > $truckPosition && $i <= $house['y']) || ($i < $truckPosition && $i >= $house['y'])) {
          $houses[] = [
            'x' => $house['x'],
            'y' => $i,
          ];
        }
      }
    }

    return $houses;
  }
}
