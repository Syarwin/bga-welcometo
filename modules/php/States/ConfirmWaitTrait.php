<?php
namespace WTO\States;

use \WTO\Actions\RealEstate;
use \WTO\Actions\Park;
use \WTO\Actions\Temp;
use \WTO\Actions\Bis;
use \WTO\Actions\Pool;
use \WTO\Actions\Surveyor;

use \WTO\Game\Players;
use \WTO\Game\Globals;
use \WTO\Game\Log;
use \WTO\Game\StateMachine;
use \WTO\Game\UserException;

/*
 * Handle the confirm/restart and wait
 */
trait ConfirmWaitTrait
{
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
