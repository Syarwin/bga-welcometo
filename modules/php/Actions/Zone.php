<?php
namespace WTO\Actions;
use \WTO\Scribbles;

/*
 * Zone : generic handler of given zone type where you can scribble
 *    most part are just 1d or 2d array where the next scribble you can take if the first available in the column
 *    the idea is to handle that in a generic way
 *    this includes : park (2D, 1 for each streets), score pool (1D), score estate (2D), score temp (1D)
 */
class Zone
{
  // The name of zones, eg 'score-estate'. Must correspond to the name of the container class of the jstpl
  protected static $type = "";
  // Need one or two params ? default is 2 : x,y
  protected static $dim = 2;
  // Size of the different zone, if 1d just an int
  protected static $cols = [];


  /*
   * Get all the scribbles of given player corresponding to this type
   */
  public function getOfPlayer($player)
  {
    return Scribbles::getOfPlayer($player, static::$type."_%");
  }

  /*
   * Construct a (1D or 2D) array corresponding with the zones structure with null values
   */
  protected function getBlankStructure()
  {
    $structure = [];
    if(static::$dim == 1){
      for($i = 0; $i < static::$cols; $i++)
        $structure[$i] = null;
    } else {
      for($i = 0; $i < count(static::$cols); $i++){
        $structure[$i] = [];
        for($j = 0; $j < static::$cols[$i]; $j++)
          $structure[$i][$j] = null;
      }
    }

    return $structure;
  }


  /*
   * Get all the scribbles of given player structured in a (1D or 2D) array that match the zones layout
   */
  public function getOfPlayerStructured($player){
    $zones = self::getBlankStructure();
    $scribbles = self::getOfPlayer($player);

    foreach($scribbles as $scribble){
      if(static::$dim == 1){
        if(array_key_exists($scribble['x'], $zones)){
          $zones[$scribble['x']] = $scribble;
        }
      } else {
        if(array_key_exists($scribble['x'], $zones) && array_key_exists($scribble['y'], $zones[$scribble['x']])){
          $zones[$scribble['x']][$scribble['y']] = $scribble;
        }
      }
    }
    return $zones;
  }

  public function getAvailableZones($player){
    $zones = self::getOfPlayerStructured($player);
    $result = [];

    // 1D : return the index of the first available location
    if(static::$dim == 1){
      for($i = 0; $i < static::$cols; $i++){
        if(is_null($zones[$i])){
          array_push($result, [$i]);
          break;
        }
      }
    }
    // 2D : return the index of the first available location for each row
    else {
      for($i = 0; $i < count(static::$cols); $i++){
        for($j = 0; $j < static::$cols[$i]; $j++){
          if(is_null($zones[$i][$j])){
            array_push($result, [$i,$j]);
            break;
          }
        }
      }
    }

    return $result;
  }
}
