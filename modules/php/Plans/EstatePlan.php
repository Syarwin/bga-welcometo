<?php
namespace WTO\Plans;


function subtract_array($array1,$array2){
  foreach ($array2 as $item) {
    $key = array_search($item, $array1);
    if($key !== false)
      unset($array1[$key]);
  }
  return array_values($array1);
}


class EstatePlan extends AbstractPlan
{
  public function canBeScored($player){
    $estates = $player->getEstates(false); // False means only not used yet estates
    $sizes = array_map(function($estate){ return $estate[2]; }, $estates);

    $diff = substract_array($this->conditions, $sizes);
    return empty($diff);
  }

  public function argValidatePlans($player){
    return [];
  }

  public function validatePlans($player, $args){

  }
}
