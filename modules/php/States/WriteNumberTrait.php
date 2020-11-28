<?php
namespace WTO\States;

use \WTO\Actions\RealEstate;
use \WTO\Actions\Park;
use \WTO\Actions\Temp;
use \WTO\Actions\Bis;
use \WTO\Actions\Pool;
use \WTO\Actions\Surveyor;
use \WTO\Actions\PermitRefusal;
use \WTO\Actions\Roundabout;

use \WTO\Game\Players;
use \WTO\Game\Globals;
use \WTO\Game\Log;
use \WTO\Game\StateMachine;
use \WTO\Game\UserException;
use \WTO\Game\Notifications;

/*
 * Handle the choose cards / write number stuff
 */
trait WriteNumberTrait
{

  ///////////////////////////////
  //////// CHOOSE CARDS /////////
  ///////////////////////////////
  function argChooseCards($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data['selectableStacks'] = $player->getAvailableStacks();
    if(empty($data['selectableStacks'])){
      $data['zones'] = PermitRefusal::getAvailableZones($player);
    }

    // Advanced variant : can build roundabout
    if(Globals::isAdvanced()){
      $zones = Roundabout::getAvailableZones($player);
      if(!empty($zones) && is_null($player->getLastHouse()))
        $data['canBuildRoundabout'] = true;
    }

    return $data;
  }

  function chooseCards($stack)
  {
    // Sanity checks
    StateMachine::checkAction("chooseCards");
    $player = Players::getCurrent();
    $args = self::argChooseCards($player);
    if(!in_array($stack, $args['selectableStacks']))
      throw new UserException(totranslate("You cannot select this stack"));

    // Do the action (logging the choice for rest of the turn)
    $player->chooseCards($stack);

    $combination = $player->getCombination();
    Notifications::messageTo($player, clienttranslate('You choose the combination : ${number} & ${action}'), [
      'i18n' => ['action'],
      'action' => ACTION_NAMES[$combination["action"]],
      'number' => $combination["number"],
    ]);

    // Move on to next state
    StateMachine::nextState("writeNumber");
  }


  ///////////////////////
  //// PERMIT REFUSAL ///
  ///////////////////////
  function permitRefusal()
  {
    $player = Players::getCurrent();

    // Write the next available spot in the score sheet
    $zones = PermitRefusal::getAvailableZones($player);
    if(empty($zones))
      throw new UserException(totranslate("You cannot take a permit refusal if a pair of construction cards is playable"));

    $player->scribbleZone($zones[0]);
    $player->updateScores();

    StateMachine::nextState("refusal");
  }

  ///////////////////////////////
  //////// WRITE NUMBER /////////
  ///////////////////////////////
  function argWriteNumber($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data["numbers"] = $player->getAvailableNumbers();
    return $data;
  }

  function writeNumber($number, $pos)
  {
    // Sanity checks
    StateMachine::checkAction("writeNumber");

    // Advanced variant : roundabout
    if($number == ROUNDABOUT){
        $this->buildRoundabout($pos);
        return;
    }

    $player = Players::getCurrent();
    $args = self::argWriteNumber($player);
    if(!isset($args['numbers'][$number]) || !in_array($pos, $args['numbers'][$number]))
      throw new UserException(totranslate("You cannot write this number in this house"));


    // Write the number on the house
    $player->writeNumber($number, $pos);
    $player->updateScores();

    // Move on to next state depending on the action card
    $combination = $player->getCombination();
    StateMachine::nextState($combination["action"]);
  }


  ///////////////////////////////
  ///////// ROUNDABOUT //////////
  ///////////////////////////////
  function roundabout()
  {
    StateMachine::nextState("roundabout");
  }


  function argRoundabout($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data["numbers"] = [
      ROUNDABOUT => $player->getAvailableHousesForNumber(null)
    ];
    return $data;
  }


  function buildRoundabout($pos)
  {
    // Sanity check
    $player = Players::getCurrent();
    $args = self::argRoundabout($player);
    if(!in_array($pos, $args['numbers'][ROUNDABOUT]))
      throw new UserException(totranslate("You cannot build a roundabout in this house box"));

    $zones = Roundabout::getAvailableZones($player);
    if(empty($zones))
      throw new UserException(totranslate("You already built your two roundabout"));



    // Write the number on the house
    $player->writeNumber(ROUNDABOUT, $pos);
    $player->scribbleZone([$pos[0], $pos[1] - 1], "estate-fence");
    $player->scribbleZone($pos, "estate-fence");
    $player->scribbleZone($zones[0], "estate-fence");
    $player->scribbleZone($zones[0]);
    $player->updateScores();

    StateMachine::nextState("built");
  }

}
