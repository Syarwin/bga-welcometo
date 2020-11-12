<?php
namespace WTO\States;

use \WTO\Game\Players;
use \WTO\Game\Globals;
use \WTO\Game\Log;
use \WTO\Game\StateMachine;
use \WTO\Game\UserException;

/*
 * Handle the plans validation
 */
trait PlanValidationTrait
{
  /*
   * Fetch the basic info a player should have no matter in which private state he is :
   *   - selected plan cards (if any)
   *   - cancelable flag on if an action was already done by user
   */
  function argPrivatePlayerPlanTurn($player)
  {
    $data = [
//      'selectedCards' => $player->getSelectedCards(),
//      'cancelable' => $player->hasSomethingToCancel(),
    ];

    return $data;
  }


  ///////////////////////////////
  //////// CHOOSE CARDS /////////
  ///////////////////////////////
  function argChoosePlan($player)
  {
    $data = $this->argPrivatePlayerPlanTurn($player);
    $data['selectablePlans'] = $player->getAvailablePlans();
    return $data;
  }

  function chooseCards($stack)
  {
    // Sanity checks
    StateMachine::checkAction("choosePlan");
    $player = Players::getCurrent();
    $args = self::argChooseCards($player);
    if(!in_array($stack, $args['selectableStacks']))
      throw new UserException(totranslate("You cannot select this stack"));

    // Do the action (logging the choice for rest of the turn)
    $player->chooseCards($stack);

    // Move on to next state
    StateMachine::nextState("writeNumber");
  }
}
