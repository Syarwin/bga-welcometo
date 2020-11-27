<?php
namespace WTO\Plans;
use \WTO\Actions\Temp;

class SevenTempPlan extends AbstractPlan
{
  protected $automatic = true;

  public function __construct($info, $card = null){
    parent::__construct($info, $card);

    $this->desc = [
      clienttranslate("To fulfill this City Plan, 7 temps must be hired."),
    ];
  }

  public function canBeScored($player)
  {
    if(!parent::canBeScored($player))
      return false;

    // Get empty locations
    $zones = Temp::getAvailableZones($player);
    return empty($zones) || $zones[0][0] > 6;
  }
}
