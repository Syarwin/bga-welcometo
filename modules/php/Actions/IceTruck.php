<?php
namespace WTO\Actions;
use WTO\Houses;

/*
 * IceTruck : manage marking related to ice trucks
 */
class IceTruck extends Zone
{
  protected static $type = 'ice-truck';
  protected static $cols = [10, 11, 12, 2]; // Last row is a fake one used to cross one truck off

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
    $positions[2] = null;
    if (!is_null($structure[3][1])) {
      for ($i = 0; $i < 12 && !is_null($structure[2][$i]); $i++);
      $positions[2] = $i - 1;
    } elseif (!is_null($structure[3][0])) {
      for ($i = 11; $i > 0 && !is_null($structure[2][$i]); $i--);
      $positions[2] = $i + 1;
    }

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
