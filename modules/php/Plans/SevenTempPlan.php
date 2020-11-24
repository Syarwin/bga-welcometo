<?php
namespace WTO\Plans;
use \WTO\Actions\Temp;

class SevenTempPlan extends AbstractPlan
{
  protected $automatic = true;

  public function canBeScored($player)
  {
    if(!parent::canBeScored($player))
      return false;

    // Get empty locations
    $zones = Temp::getAvailableZones($player);
    return empty($zones) || $zones[0] > 6;
  }
}
