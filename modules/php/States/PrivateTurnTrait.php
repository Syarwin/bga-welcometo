<?php
namespace WTO\States;
use WTO\Globals;

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
    return $data;
  }

}
