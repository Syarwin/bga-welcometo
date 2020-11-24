<?php
namespace WTO\Plans;
use WTO\Scribbles;
use WTO\Actions\Park;
use WTO\Actions\Pool;

class DecorativePlan extends AbstractPlan
{
  protected $automatic = true;

  public function canBeScored($player)
  {
    if(!parent::canBeScored($player))
      return false;


    switch($this->conditions[0]){
      case 'park';
        return count(Park::getAvailableZones($player)) < 1;

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
