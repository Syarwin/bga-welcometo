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
  /**
   * Auto-confirm is setting is on
   */
  function stConfirmTurn($player)
  {
    $pref = $player->getPref(AUTOMATIC);
    if($pref == ENABLED){
      $this->confirmTurn();
      return true;
    }
  }

  function cancelTurn()
  {
    StateMachine::checkAction("restart");
    $player = Players::getCurrent();
    $player->restartTurn();
    $player->updateScores();

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
