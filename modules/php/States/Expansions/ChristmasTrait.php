<?php
namespace WTO\States\Expansions;

use WTO\Actions\Christmas;

use WTO\Game\Globals;
use WTO\Game\Players;
use WTO\Game\Log;
use WTO\Game\StateMachine;
use WTO\Game\UserException;
use WTO\Game\Notifications;
use WTO\Helpers\Utils;
use WTO\Scribbles;

/*
 * Handle the Christmas expansion
 */
trait ChristmasTrait
{
  /**
   * stIceCream: check if ice cream can be distributed already or if we need a choice to be made for the third row
   */
  public function suspendChristmasDecoration($player)
  {
    $house = $player->getLastHouse();

    // Add the scribbles for christmas decoration
    $scribbles = [];
    $zones = Christmas::getChristmasToScribble($player);
    if(empty($zones)){
      return;
    }

    foreach ($zones as $zone) {
      array_push($scribbles, Scribbles::add($player->getId(), 'christmas', $zone));
    }

    // Notify all the new scribbles
    Notifications::addMultipleScribbles($player, $scribbles);
    $player->updateScores();
  }
}
