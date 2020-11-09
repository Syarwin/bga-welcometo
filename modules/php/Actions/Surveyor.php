<?php
namespace WTO\Actions;
use WTO\Houses;

/*
 * Park : manage everything related to side fences
 */
class Surveyor extends Zone
{
  protected static $type = "estate-fence";
  protected static $cols = [9,10,11];

  public function getAvailableZones($player)
  {
    $fences = parent::getOfPlayerStructured($player);
    $houses = Houses::getStreets($player->getId());
    $zones = [];

    for($i = 0; $i < 3; $i++){
      for($j = 0; $j < static::$cols[$i]; $j++){
        // Not already a fence
        if(!is_null($fences[$i][$j]))
          continue;

        if(!is_null($houses[$i][$j])){
          // Not used in plan
          if($houses[$i][$j]['usedInPlan'])
            continue;

          // Can't separate a bis
          if(!is_null($houses[$i][$j+1]) && $houses[$i][$j]['number'] == $houses[$i][$j+1]['number'])
            continue;
        }

        array_push($zones, [$i, $j]);
      }
    }

    return $zones;
  }
}
