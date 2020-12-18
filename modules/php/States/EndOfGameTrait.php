<?php
namespace WTO\States;

use WTO\Game\StateMachine;
use WTO\Game\Players;
use WTO\Game\Notifications;
use WTO\Game\Stats;
use WTO\Game\Globals;
use WTO\Houses;
use WTO\PlanCards;
use WTO\ConstructionCards;
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
      $cardsInDeck = ConstructionCards::getInLocation('deck');

      if($returnTypeOfEOG && empty($freeSpots)) return 1;
      if($returnTypeOfEOG && $areAllPlansScored) return 2;
      if($returnTypeOfEOG && empty($freePermitRefusalZones)) return 3;
      if($returnTypeOfEOG && $cardsInDeck->empty() && Globals::isSolo()) return 4;


      if(empty($freeSpots) || $areAllPlansScored || empty($freePermitRefusalZones) || ($cardsInDeck->empty() && Globals::isSolo()))
        return true;
    }

    return false;
  }


  /*
   *
   */
  function stComputeScores()
  {
    // Notify end of game
    $type = self::isEndOfGame(true);
    $msgs = [
      1 => clienttranslate("An architect has built all of the houses on his three streets, triggering end of game."),
      2 => clienttranslate("A player achieved all three plans, triggering end of game."),
      3 => clienttranslate("A player checked his third building permit refusal, triggering end of game."),
      4 => clienttranslate("The deck is empty, triggering end of game in solo mode."),
    ];
    Notifications::message($msgs[$type]);

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
    $i = 0;
    $previous = [];
    foreach(array_keys($scoresAux) as $i => $pId){
      if($scoresAux[$pId] != $previous){
        $previous = $scoresAux[$pId];
        $i++;
      }
      Players::DB()->update(['player_score_aux' => $i])->run($pId);
    }

    // Stats
    $endOfGameType = self::isEndOfGame(true);
    Stats::endOfGame($endOfGameType);

    $this->gamestate->nextState("endGame");
  }


}
