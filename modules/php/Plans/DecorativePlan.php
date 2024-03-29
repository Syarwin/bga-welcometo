<?php
namespace WTO\Plans;
use WTO\Scribbles;
use WTO\Actions\Park;
use WTO\Actions\Pool;

class DecorativePlan extends AbstractPlan
{
  protected $automatic = true;

  public function __construct($info, $card = null){
    parent::__construct($info, $card);

    $type = $this->conditions[0];
    if($type == 'park'){
      $this->desc = [ clienttranslate("To fulfill this City Plan, two streets must have all of the parks built.") ];
    }

    else if($type == 'pool'){
      $this->desc = [ clienttranslate("To fulfill this City Plan, two streets must have all of the pools built.") ];
    }

    else if($type == 'pool&park'){
      $this->desc = [  clienttranslate("To fulfill this City Plan, all of the parks AND all of the pools on the required street must be built.") ];
    }
  }

  public function canBeScored($player)
  {
    if(!parent::canBeScored($player))
      return false;


    switch($this->conditions[0]){
      case 'park';
        return count(Park::getAvailableZones($player, false)) <= 1;

      case 'pool':
        $pools = Pool::getCompleted($player);
        $n = 0;
        for($i = 0; $i < 3; $i++)
          $n += $pools[$i]? 1 : 0;
        return $n >= 2;

      case 'pool&park':
        $parks = [true, true, true];
        foreach(Park::getAvailableZones($player, false) as $zone){
          $parks[$zone[0]] = false;
        }
        $pools = Pool::getCompleted($player);

        $x = $this->conditions[1];
        return $parks[$x] && $pools[$x];

      default:
        return false;
    }
  }
}
