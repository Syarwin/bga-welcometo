<?php
namespace WTO\States;

use WTO\Game\StateMachine;
use WTO\Game\Players;
use WTO\Game\Stats;
use WTO\Houses;
use WTO\PlanCards;
use WTO\Actions\PermitRefusal;

/*
 * Handle the end of game
 */

trait EndOfGameTrait
{
  function isEndOfGame($returnTypeOfEOG = false)
  {
    foreach(Players::getAll() as $player){
      $freeSpots = Houses::getAvailableLocations($player);
      $areAllPlansScored = PlanCards::areAllPlansScored($player);
      $freePermitRefusalZones = PermitRefusal::getAvailableZones($player);

      if($returnTypeOfEOG && empty($freeSpots)) return 'all_houses_ending';
      if($returnTypeOfEOG && $areAllPlansScored) return 'projects_ending';
      if($returnTypeOfEOG && empty($freePermitRefusalZones)) return 'permit_refusal_ending';

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
    // Compute aux
    //In case of a draw, the player with the most completed estates wins. In case of another draw, the one with the most size 1 estates wins, then size 2, etc.
    $scoresAux = [];
    foreach(Players::getAll() as $player){
      $estates = $player->getEstates();
      $data = [count($estates), 0,0,0,0,0,0];
      foreach($estates as $estate){
        $data[$estate['size']]++;
      }
      $scoresAux[$player->getId()] = $data;
    }

    // Sorting
    uasort($scoresAux, function($p1, $p2){
      for($i = 0; $i < 7; $i++){
        if($p1[$i] != $p2[$i])
          return $p1[$i] - $p2[$i];
      }
      return 0;
    });

    // Store them
    foreach(array_keys($scoresAux) as $i => $pId){
      Players::DB()->update(['player_score_aux' => $i])->run($pId);
    }

    // Stats
    $endOfGameType = self::isEndOfGame(true);
    Stats::endOfGame($endOfGameType);

    $this->gamestate->nextState("endGame");
  }


}
