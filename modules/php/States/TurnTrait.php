<?php
namespace WTO\States;

use WTO\Game\StateMachine;
use WTO\Game\Notifications;
use WTO\Game\Players;
use WTO\ConstructionCards;

/*
 * Handle the public start and end of a turn
 */

trait TurnTrait
{
  /*
   * Game state that init a new turn
   */
  function stNewTurn()
  {
    // Increase turn number
    $n = (int) self::getGamestateValue('currentTurn') + 1;
    self::setGamestateValue("currentTurn", $n);
    ConstructionCards::draw();

    StateMachine::initPrivateStates(ST_PLAYER_TURN);
    $this->gamestate->nextState("playerTurn");
  }


  /*
   * Multi-active state where each player can take their turn
   */
  function stPlayerTurn()
  {
    $ids = Players::getAll()->getIds();
    $this->gamestate->setPlayersMultiactive($ids, '');
  }

  /*
   * The arg depends on the private state of each player
   */
  function argPlayerTurn()
  {
    return StateMachine::getArgs();
  }

  /*
   * Fetch the basic info a player should have no matter in which private state he is :
   *   - selected construction cards (if any)
   *   - cancelable flag on if an action was already done by user
   */
  function argPrivatePlayerTurn($player)
  {
    $data = [
      'selectedCards' => $player->getSelectedCards(),
      'selectedPlans' => $player->getSelectedPlans(),
      'cancelable' => $player->hasSomethingToCancel(),
    ];

    return $data;
  }

  /*
   *
   */
  function stApplyTurn()
  {
    // Compute, store and notify new scores
    $scores = [];
    foreach (Players::getAll() as $player) {
      $scores[$player->getId()] = $player->storeScore();
    }
    Notifications::updateAllPlayersScores($scores);

    $newState = $this->isEndOfGame()? "endGame" : "newTurn";
    $this->gamestate->nextState($newState);
  }
}
