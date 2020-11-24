<?php
namespace WTO\Plans;
use \WTO\Houses;

class FiveBisPlan extends AbstractPlan
{
  protected $automatic = true;

  public function canBeScored($player)
  {
    if(!parent::canBeScored($player))
      return false;

    $bis = [0, 0, 0];
    foreach(Houses::getOfPlayer($player) as $house){
      if($house['isBis'])
        $bis[$house['x']]++;
    }

    return $bis[0] > 4 || $bis[1] > 4 || $bis[2] > 4;
  }
}
