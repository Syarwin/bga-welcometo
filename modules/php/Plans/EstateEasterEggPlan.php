<?php
namespace WTO\Plans;
use WTO\Scribbles;
use WTO\Game\UserException;
use WTO\Game\Notifications;
use WTO\Helpers\Utils;
use WTO\Actions\EasterEgg;

class EstateEasterEggPlan extends EstatePlan
{
  public function __construct($info, $card = null)
  {
    parent::__construct($info, $card);
    $this->conditions = array_slice($info[4], 1);
    $this->eggCondition = $info[4][0] == 'with';

    $this->desc = $this->eggCondition
      ? [clienttranslate('Build 3 estates of 3 houses with at least 3 collected eggs in it.')]
      : [clienttranslate('Build 1 estate of 2 houses, 1 of 3 houses and 1 of 4 houses without any collected egg.')];
  }

  protected function getAvailableEstates($player)
  {
    $estates = parent::getAvailableEstates($player);
    $eggs = EasterEgg::getOfPlayerStructured($player);

    Utils::filter($estates, function ($estate) use ($eggs) {
      $nEggs = 0;
      for ($i = 0; $i < $estate['size']; $i++) {
        if (!is_null($eggs[$estate['x']][$estate['y'] + $i])) {
          $nEggs += $eggs[$estate['x']][$estate['y'] + $i]['state'];
        }
      }

      return ($this->eggCondition && $nEggs >= 3) || (!$this->eggCondition && $nEggs == 0);
    });
    return $estates;
  }
}
