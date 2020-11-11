<?php
namespace WTO\Actions;
use WTO\Houses;

/*
 * RealEstate : manage everything related to real estates
 */
class RealEstate extends Zone
{
  protected static $type = "score-estate";
  protected static $cols = [1,2,3,4,4,4];


  public function getEstates($player)
  {
    $streets = Houses::getStreets($player->getId());
    $fences = Surveyor::getOfPlayerStructured($player);

    $estates = [];
    for($i = 0; $i < 3; $i++){
      $start = 0;
      $full = true;
      for($j = 0; $j < count($streets[$i]); $j++){
        // TODO : handle turnaround
        if(is_null($streets[$i][$j])){
          $full = false;
        }

        // If no hole and a fence on the right (either built or right edge of street)
        if($j == count($streets[$i]) - 1 || !is_null($fences[$i][$j]) ){
          if($full)
            array_push($estates, [$i, $start, $j - $start + 1]);
          $full = true;
          $start = $j + 1;
        }
      }
    }

    return $estates;
  }

  public function getMultipliers($player)
  {
    $mult = [0,0,0,0,0,0];
    foreach(self::getEstates($player) as $estate){
      $size = $estate[2];
      if($size < 7)
        $mult[$size - 1]++;
    }

    return $mult;
  }


  protected static $scores = [
    [1, 3],
    [2, 3, 4],
    [3, 4, 5, 6],
    [4, 5, 6, 7, 8],
    [5, 6, 7, 8, 10],
    [6, 7, 8, 10, 12],
  ];
  public function getScore($player)
  {
    // Compute the score of each estate size
    $free = [1, 2, 3, 4, 4, 4]; // Index position if no zone is available
    foreach(parent::getAvailableZones($player) as $zone)
      $free[$zone[0]] = $zone[1];

    // Compute the number of estates of each size
    $mult = self::getMultipliers($player);

    // Return scores
    return [
      'estate-mult-0' => $mult[0],
      'estate-mult-1' => $mult[1],
      'estate-mult-2' => $mult[2],
      'estate-mult-3' => $mult[3],
      'estate-mult-4' => $mult[4],
      'estate-mult-5' => $mult[5],

      'estate-total-0' => $mult[0]*self::$scores[0][$free[0]],
      'estate-total-1' => $mult[1]*self::$scores[1][$free[1]],
      'estate-total-2' => $mult[2]*self::$scores[2][$free[2]],
      'estate-total-3' => $mult[3]*self::$scores[3][$free[3]],
      'estate-total-4' => $mult[4]*self::$scores[4][$free[4]],
      'estate-total-5' => $mult[5]*self::$scores[5][$free[5]],
    ];
  }
}
