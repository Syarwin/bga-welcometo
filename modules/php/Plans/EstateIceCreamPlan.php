<?php
namespace WTO\Plans;
use WTO\Scribbles;
use WTO\Game\UserException;
use WTO\Game\Notifications;
use WTO\Helpers\Utils;
use WTO\Actions\IceCream;

class EstateIceCreamPlan extends EstatePlan
{
  public function __construct($info, $card = null)
  {
    parent::__construct($info, $card);
    $this->conditions = array_slice($info[4], 1);
    $this->iceCreamCondition = $info[4][0] == 'with';

    $this->desc = $this->iceCreamCondition
      ? [clienttranslate('Build 3 estates of 4 houses with 3 ice cream cones sold in it.')]
      : [
        clienttranslate('Build 1 estate of 3 houses, 1 of 4 houses and 1 of 5 houses without any ice cream cone sold.'),
      ];
  }

  protected function getAvailableEstates($player)
  {
    $estates = parent::getAvailableEstates($player);
    $cones = IceCream::getOfPlayerStructured($player);

    Utils::filter($estates, function ($estate) use ($cones) {
      $iceCream = 0;
      for ($i = 0; $i < $estate['size']; $i++) {
        if (!is_null($cones[$estate['x']][$estate['y'] + $i]) && $cones[$estate['x']][$estate['y'] + $i]['state'] == 1) {
          $iceCream++;
        }
      }

      return ($this->iceCreamCondition && $iceCream >= 3) || (!$this->iceCreamCondition && $iceCream == 0);
    });
    return $estates;
  }
}
