<?php
namespace WTO\States;
use WTO\Players;
use WTO\Globals;
use WTO\Log;
use WTO\StateMachine;

/*
 * Handle the private state of each player during the turn
 */
trait PrivateTurnTrait
{
  /*
   * Fetch the basic info a player should have no matter in which private state he is :
   *   - turn number to highlight last actions
   *   - construction cards (might depend on the variant)
   *   - selected construction cards (if any)
   *   - cancelable flag on if an action was already done by user
   */
  function argPrivatePlayerTurn($player)
  {
    $data = [
      'turn' => Globals::getCurrentTurn(),
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
    $data['selectableStacks'] = [0, 1]; // TODO filter stack depending on playable combinaison + handle non standard
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

    // Do the action
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

    // Do the action
    $player->writeNumber($number, $pos);

    // Move on to next state
    StateMachine::nextState("bis");
  }




  //////////////////////////////////////////
  //////////////////////////////////////////
  ///////// NON-AUTOMATIC ACTIONS //////////
  //////////////////////////////////////////
  //////////////////////////////////////////
  function passAction()
  {
    StateMachine::checkAction("pass");
    // TODO : Log the passing ?
    StateMachine::nextState("pass");
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
    StateMachine::nextState("restart");
  }
}
