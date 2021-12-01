<?php
namespace WTO\Plans;
use WTO\Scribbles;
use WTO\Game\UserException;
use WTO\Game\Notifications;
use WTO\Helpers\Utils;
use WTO\Actions\Christmas;

class EstateChristmasPlan extends EstatePlan
{
  public function __construct($info, $card = null)
  {
    parent::__construct($info, $card);
    $this->conditions = array_slice($info[4], 1);
    $this->lightCondition = $info[4][0] == 'with';

    $this->desc = $this->lightCondition
      ? [clienttranslate('Build 2 estates of 6 houses connected to each other by a single string of light.')]
      : [clienttranslate('Build 2 estates of 3 houses without any string of lights')];
  }

  protected function getAvailableEstates($player)
  {
    $estates = parent::getAvailableEstates($player);
    $christmas = Christmas::getOfPlayerStructured($player);

    Utils::filter($estates, function ($estate) use ($christmas) {
      $lights = 0;
      for ($i = 0; $i < $estate['size'] - 1; $i++) {
        if (!is_null($christmas[$estate['x']][$estate['y'] + $i])) {
          $lights++;
        }
      }

      return ($this->lightCondition && $lights == 5) || (!$this->lightCondition && $lights == 0);
    });
    return $estates;
  }
}
