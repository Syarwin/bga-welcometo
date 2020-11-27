<?php
namespace WTO\Plans;
use \WTO\Scribbles;
use \WTO\Houses;
use \WTO\Game\Notifications;
use \WTO\Actions\Park;
use \WTO\Actions\Pool;


class CompleteStreetPlan extends AbstractPlan
{
  protected $automatic = true;

  public function __construct($info, $card = null){
    parent::__construct($info, $card);

    $this->desc = [
      clienttranslate("To fulfill this City Plan, the player must build all of the parks, all of the pools, and one roundabout in the same street."),
    ];
  }



  public function canBeScored($player)
  {
    if(!parent::canBeScored($player))
      return false;

    $streets = [true, true, true];
    // Park must be finished
    foreach(Park::getAvailableZones($player, false) as $zone){
      $streets[$zone[0]] = false;
    }

    // Pool must be finished
    $pools = Pool::getCompleted($player);
    for($i = 0; $i < 3; $i++)
      $streets[$i] = $streets[$i] && $pools[$i];

    // Roundabout must be built
    $roundabouts = [false, false, false];
    foreach(Houses::getOfPlayer($player) as $house){
      if($house['number'] == ROUNDABOUT)
        $roundabouts[$house['x']] = true;
    }
    for($i = 0; $i < 3; $i++)
      $streets[$i] = $streets[$i] && $roundabouts[$i];

    return $streets[0] || $streets[1] || $streets[2];
  }
}
