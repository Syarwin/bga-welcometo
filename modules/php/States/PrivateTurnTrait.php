<?php
namespace WTO\States;
use \WTO\RealEstate;
use \WTO\Park;
use \WTO\Game\Players;
use \WTO\Game\Globals;
use \WTO\Game\Log;
use \WTO\Game\StateMachine;

/*
 * Handle the private state of each player during the turn
 */
trait PrivateTurnTrait
{
  /*
   * Fetch the basic info a player should have no matter in which private state he is :
   *   - selected construction cards (if any)
   *   - cancelable flag on if an action was already done by user
   */
  function argPrivatePlayerTurn($player)
  {
    $data = [
      'selectedCards' => $player->getSelectedCards(),
      'cancelable' => $player->hasSomethingToCancel(),
    ];

    return $data;
  }



  ///////////////////////////////
  //////// CHOOSE CARDS /////////
  ///////////////////////////////
  function argChooseCards($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data['selectableStacks'] = [0, 2]; // TODO filter stack depending on playable combinaison + handle non standard
    return $data;
  }

  function chooseCards($stack)
  {
    // Sanity checks
    StateMachine::checkAction("chooseCards");
    $player = Players::getCurrent();
    $args = self::argChooseCards($player);
    if(!in_array($stack, $args['selectableStacks']))
      throw new \BgaUserException(clienttranslate("You cannot select this stack"));

    // Do the action (logging the choice for rest of the turn)
    $player->chooseCards($stack);

    // Move on to next state
    StateMachine::nextState("writeNumber");
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
      throw new \BgaUserException(clienttranslate("You cannot write this number in this house"));

    // Write the number on the house
    $player->writeNumber($number, $pos);

    // Move on to next state depending on the action card
    $combination = $player->getCombination();
    StateMachine::nextState($combination["action"]);
  }




  //////////////////////////////////////////
  //////////////////////////////////////////
  //////////////   ACTIONS   ///////////////
  //////////////////////////////////////////
  //////////////////////////////////////////
  function passAction()
  {
    StateMachine::checkAction("pass");
    // TODO : Log the passing ?
    StateMachine::nextState("pass");
  }

  /*
   * Generic scribble zone action
   */
  function scribbleZone($zone)
  {
    StateMachine::checkAction("scribbleZone");
    $player = Players::getCurrent();
    $args = StateMachine::getArgsOfPlayer($player);
    if(!in_array($zone, $args['zones']))
      throw new \BgaUserException(clienttranslate("You cannot scribble this zone"));

    $player->scribbleZone($zone);
    StateMachine::nextState("scribbleZone");
  }


  ///////////////////////////
  ///////// ESTATE //////////
  ///////////////////////////
  function argActionEstate($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data['zones'] = RealEstate::getAvailableZones($player);
    return $data;
  }


  ///////////////////////////
  ///////// PARKS ///////////
  ///////////////////////////
  function argActionPark($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data['zones'] = Park::getAvailableZones($player);
    return $data;
  }


  ///////////////////////////////
  ///////// ACTION BIS //////////
  ///////////////////////////////
  function argActionBis($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data["numbers"] = $player->getAvailableNumbersForBis();
    return $data;
  }

  function writeNumberBis($number, $pos)
  {
    // Sanity checks
    StateMachine::checkAction("writeNumberBis");
    $player = Players::getCurrent();
    $args = self::argActionBis($player);
    if(!isset($args['numbers'][$number]) || !in_array($pos, $args['numbers'][$number]))
      throw new \BgaUserException(clienttranslate("You cannot write this number bis in this house"));

    // Do the action
    $player->writeNumber($number, $pos, true);

    // Move on to next state
    StateMachine::nextState("bis");
  }



  //////////////////////////////////////
  ///////// CONFIRM / RESTART //////////
  //////////////////////////////////////
  function cancelTurn()
  {
    StateMachine::checkAction("restart");
    $player = Players::getCurrent();
    $player->restartTurn();
    $this->gamestate->setPlayersMultiactive([$player->getId()], '');
    StateMachine::nextState("restart");
  }

  function confirmTurn()
  {
    StateMachine::checkAction("confirm");
    StateMachine::nextState("confirm");
  }

  /*
   * Make the player inactive and wait for other
   */
  function stWaitOther($player)
  {
    return $this->gamestate->setPlayerNonMultiactive($player->getId(), "applyTurns");
  }
}
