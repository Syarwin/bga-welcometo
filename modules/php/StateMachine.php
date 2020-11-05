<?php

namespace WTO;
use welcometo;

/*
 * StateMachine: a class that allows to emulate parallel game states flow when in a multiactive state
 *    !!! Your player table should have an additional int field matching the 'stateField' static variable !!!
 *   eg : ALTER TABLE `player` ADD `player_state` INT(10) UNSIGNED;
 */
class StateMachine extends \APP_DbObject
{
  private static $stateField = 'player_state';
  private static function getGame()
  {
    return welcometo::get();
  }
  private static function getGamestate()
  {
    return self::getGame()->gamestate;
  }



  /*
   * Get "normal" state of the framework
   */
  public static function getPublicState()
  {
    return self::getGameState()->state();
  }

  /*
   * Get private state of a player, can be called with
   *   - only a player id, in this case the private state id is fetched from DB
   *   - with the private state id, and $fetch = false
   * and will return corresponding data from state machine
   */
  public function getPrivateState($mixed, $fetch = true)
  {
    $stateId = $fetch ? self::getUniqueValueFromDB("SELECT ".self::$stateField." FROM player WHERE player_id = $mixed") : $mixed;
    $states = self::getGameState()->states;
    if(!array_key_exists($stateId, $states))
      throw new \BgaVisibleSystemException("Cannot fetch private state of a player in the state machine : player $mixed, state $stateId");

    return $states[$stateId];
  }

  /*
   * Set the private state of a player(s)
   */
  public function setPrivateState($ids, $stateId)
  {
    $whereIds = is_array($ids)? ("IN (".implode(",", $ids) .")") : " = $ids";
    $states = self::getGameState()->states;
    if(!array_key_exists($stateId, $states))
      throw new \BgaVisibleSystemException("Cannot find private state you want to set: state $stateId on player $whereIds");

    if($states[$stateId]['type'] != "private")
      throw new \BgaVisibleSystemException("Trying to set state $stateId which is not a valid private state on player $whereIds");

    self::DbQuery("UPDATE player SET `".self::$stateField."` = $stateId WHERE player_id $whereIds");
  }


  /*
   * Sanity check: private state are only enabled in a multiactive state with the flag "parallel" set to true
   */
  public function checkParallel($stateId = null)
  {
    $stateId = $stateId ?? self::getGamestate()->state_id();
    $state = self::getGamestate()->states[$stateId];
    if(!isset($state['parallel']) || $state['type'] != 'multipleactiveplayer')
      throw new \BgaVisibleSystemException("Trying to use parallel State Machine on a non-parallel state: {$state['name']}");

    return $state['parallel'];
  }


  /*
   * Initialize parallel flow using the parallel flag of global state
   */
  public function initPrivateStates($stateId)
  {
    $privateStateId = self::checkParallel($stateId);
    $ids = self::getObjectListFromDB("SELECT player_id FROM player", true);
    self::setPrivateState($ids, $privateStateId);
  }


  /*
   * Get corresponding args for each player depending on its private state
   */
  public function getArgs()
  {
    self::checkParallel();
    $data = ['_private' => [] ];
    foreach(Players::getAll() as $player){
      $state = self::getPrivateState($player->getState(), false);
      $method = $state['args'];
      $data['_private'][$player->getId()] = [
        'state' => $state,
        'args' => self::getGame()->$method($player),
      ];
    }

    return $data;
  }

  /*
   * Get corresponding args for one player
   */
  public function getArgsOfPlayer($player)
  {
    self::checkParallel();
    $state = self::getPrivateState($player->getState(), false);
    $method = $state['args'];
    return self::getGame()->$method($player);
  }


  /*
   * Check if current action is possible for given player
   */
  public function checkAction($action, $throwException = true)
  {
    $pId = self::getGame()->getCurrentPId();
    $state = self::getPrivateState($pId);
    $found = in_array($action, $state['possibleactions']);
    if(!$found && $throwException)
      throw new \BgaVisibleSystemException("You cannot perform action '$action' in private state {$state['name']}");
    return $found;
  }


  /*
   * Update the state of a player, trigger action and send arg to update UI
   */
  public static function nextState($transition)
  {
    $pId = self::getGame()->getCurrentPId();
    $state = self::getPrivateState($pId);
    if(!isset($state['transitions'][$transition]))
      throw new \BgaVisibleSystemException("Transition '$transition' does not exist in private state {$state['name']}");

    $newStateId = $state['transitions'][$transition];
    $states = self::getGameState()->states;
    if(!isset($states[$newStateId]))
      throw new \BgaVisibleSystemException("Transition '$transition' in {$state['name']} lead to a non-existing state $newStateId");

    $newState = $states[$newStateId];
    self::setPrivateState($pId, $newStateId);
    $player = Players::get($pId);

    // Call action if it exists
    if(isset($newState['action'])){
      $actionMethod = $newState['action'];
      self::getGame()->$actionMethod($player);
    }

    // Update state and args on UI using notification
    $method = $newState['args'];
    self::getGame()->notifyPlayer($player->getId(), "newPrivateState", '', [
      'state' => $newState,
      'args' => self::getGame()->$method($player),
    ]);
  }
}
