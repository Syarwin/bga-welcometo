<?php
namespace WTO\States;

use \WTO\Actions\RealEstate;
use \WTO\Actions\Park;
use \WTO\Actions\Temp;
use \WTO\Actions\Bis;
use \WTO\Actions\Pool;
use \WTO\Actions\Surveyor;
use \WTO\Actions\PermitRefusal;

use \WTO\Game\Players;
use \WTO\Game\Globals;
use \WTO\Game\Log;
use \WTO\Game\StateMachine;
use \WTO\Game\UserException;

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
}
