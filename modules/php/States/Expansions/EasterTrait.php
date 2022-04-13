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
 * Handle the Easter expansion
 */
trait EasterTrait
{
  public function cicleEasterEggs($player)
  {
    $house = $player->getLastHouse();
    die("todo");

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
