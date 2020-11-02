<?php
namespace WTO\States;

use WTO\StateMachine;
use WTO\ConstructionCards;
use WTO\Players;

/*
 * Handle the public start and end of a turn
 */

trait PublicTurnTrait
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
}
