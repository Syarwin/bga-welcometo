<?php
namespace WTO;
use welcometo;
use WTO\Game\Globals;
use WTO\Actions\Surveyor;

/*
 * Houses manager : allows to easily access houses ...
 */
class Houses extends Helpers\DB_Manager
{
  protected static $table = 'houses';
  protected static $primary = 'id';
  protected static function cast($row)
  {
    return [
      'pId' => $row['player_id'],
      'number' => (int) $row['number'],
      'x' => (int) $row['x'],
      'y' => (int) $row['y'],
      'isBis' => $row['is_bis'] == 1,
      'turn' => (int) $row['turn'],
    ];
  }

  protected static $streetSizes = [10, 11, 12];

  public function getLast($pId)
  {
    return self::DB()->where('player_id', $pId)->where('turn', Globals::getCurrentTurn() )->get(true);
  }

  /*
   * clearTurn : remove all houses written by player during this turn
   */
  public function clearTurn($pId)
  {
    self::DB()->delete()->where('player_id', $pId)->where('turn', Globals::getCurrentTurn() )->run();
  }


  /*
   * concert a boolean locations arrays in "shape" of streets to a list of possible location
   */
  protected function convertBooleansToArray($locations)
  {
    $result = [];
    for($x = 0; $x < 3; $x++){
      for($y = 0; $y < self::$streetSizes[$x]; $y++){
        if($locations[$x][$y]){
          array_push($result, [$x, $y]);
        }
      }
    }

    return $result;
  }

  /*
   * 2D array of null matching the structure of the streets
   */
  protected function getBlankStreets($value = null)
  {
    $streets = [];
    foreach(self::$streetSizes as $size){
      $street = [];
      for($i = 0; $i < $size; $i++){
        $street[$i] = $value;
      }
      $streets[] = $street;
    }

    return $streets;
  }

  /*
   * getOfPlayer : returns the houses of given player id
   */
  public function getOfPlayer($player)
  {
    $pId = ($player instanceof \WTO\Player)? $player->getId() : $player;
    return self::DB()->where('player_id', $pId)->get(false)->toArray();
  }




  /*
   * getStreets : 2D array of houses/null
   */
  public function getStreets($player)
  {
    $streets = self::getBlankStreets();
    foreach(self::getOfPlayer($player) as $house){
      $streets[$house['x']][$house['y']] = [
        'number' => $house['number'],
        'bis' => $house['isBis'],
        'turn' => $house['turn'],
      ];
    }

    return $streets;
  }


  /*
   * getAvailableLocations of a given number
   */
  public function getAvailableLocations($player, $number = null)
  {
    // Init all locations to be available
    $locations = self::getBlankStreets(true);
    $streets = self::getStreets($player);

    // Filter the location to enforce increasing left to right
    for($x = 0; $x < 3; $x++){
      $maxNumber = -1;
      for($y = 0; $y < self::$streetSizes[$x]; $y++){
        // If the location is empty
        if(is_null($streets[$x][$y])){
          // Check condition with biggest value on left so far
          $locations[$x][$y] = is_null($number) || $number > $maxNumber;
        } else {
          // Not empty => not available and update max number
          $locations[$x][$y] = false;
          $maxNumber = ($streets[$x][$y]['number'] == ROUNDABOUT)? -1 : $streets[$x][$y]['number'];
        }
      }
    }

    // Second right to left traversal
    for($x = 0; $x < 3; $x++){
      $minNumber = 18;
      for($y = self::$streetSizes[$x] - 1; $y >= 0; $y--){
        if(is_null($streets[$x][$y])){
          $locations[$x][$y] = is_null($number) || ($locations[$x][$y] && $number < $minNumber);
        } else {
          $minNumber = ($streets[$x][$y]['number'] == ROUNDABOUT)? 18 : $streets[$x][$y]['number'];
        }
      }
    }

    return self::convertBooleansToArray($locations);
  }

  public function getAvailableLocationsForBis($player, $number)
  {
    // Init all locations to be unavailable
    $locations = self::getBlankStreets(false);
    $streets = self::getStreets($player);
    $fences = Surveyor::getOfPlayerStructured($player);


    // Add location that matches condition going left to right
    for($x = 0; $x < 3; $x++){
      $maxNumber = -1;
      $hole = 0; // Useful to ensure bis numbers are next to the the house they are copying
      for($y = 0; $y < self::$streetSizes[$x]; $y++){
        // If the location is empty
        if(is_null($streets[$x][$y])){
          $locations[$x][$y] = $number == $maxNumber && $hole == 0;
          $hole++;
        } else {
          $hole = 0;
          $maxNumber = ($streets[$x][$y]['number'] == ROUNDABOUT)? -1 : $streets[$x][$y]['number'];
        }

        if(isset($fences[$x][$y]) && !is_null($fences[$x][$y]))
          $maxNumber = -1;
      }
    }

    // Second right to left traversal that can add new locations
    for($x = 0; $x < 3; $x++){
      $minNumber = 18;
      $hole = 0;
      for($y = self::$streetSizes[$x] - 1; $y >= 0; $y--){
        if(is_null($streets[$x][$y])){
          $locations[$x][$y] = $locations[$x][$y] || ($number == $minNumber && $hole == 0);
          $hole++;
        } else {
          $hole = 0;
          $minNumber = ($streets[$x][$y]['number'] == ROUNDABOUT)? 18 : $streets[$x][$y]['number'];
        }

        if(isset($fences[$x][$y-1]) && !is_null($fences[$x][$y-1]))
          $minNumber = 18;
      }
    }

    return self::convertBooleansToArray($locations);
  }


  /*
   * Add a new house number
   */
  public static function add($pId, $number, $pos, $isBis)
  {
    // Insert return last inserted id
    $hId = self::DB()->insert([
      'player_id' => $pId,
      'number' => $number,
      'x' => $pos[0],
      'y' => $pos[1],
      'is_bis' => $isBis? 1 : 0,
      'turn' => Globals::getCurrentTurn(),
    ]);

    return self::DB()->where($hId)->get(true);
  }
}
