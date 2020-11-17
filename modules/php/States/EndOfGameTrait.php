<?php
namespace WTO\States;

use WTO\Game\StateMachine;
use WTO\Game\Players;
use WTO\Houses;
use WTO\PlanCards;
use WTO\Actions\PermitRefusal;

/*
 * Handle the end of game
 */

trait EndOfGameTrait
{
  function isEndOfGame()
  {
    foreach(Players::getAll() as $player){
      $freeSpots = Houses::getAvailableLocations($player);
      $areAllPlansScored = PlanCards::areAllPlansScored($player);
      $freePermitRefusalZones = PermitRefusal::getAvailableZones($player);
      
      if(empty($freeSpots) || $areAllPlansScored || empty($freePermitRefusalZones))
        return true;
    }

    return false;
  }


  /*
   *
   */
  function stComputeScores()
  {
    // TODO : compute tie
    $this->gamestate->nextState("endGame");
  }


}
