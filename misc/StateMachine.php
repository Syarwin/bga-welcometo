<?php

namespace WTO;
use welcometo;

/*
 * StateMachine: a class that allows to emulate parallel game states flow when in a multiactive state
 *    !!! Your player table should have an additional int field matching the 'stateField' static variable !!!
 *   eg : ALTER TABLE `player` ADD `player_state` INT(10) UNSIGNED;
 *
 * You will also need to make your game a singleton class and to expose the protected getCurrentPlayerId method
 * See code at the end of the file for an example
 *
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
    $states = self::getCollectionFromDB("SELECT player_id, ". self::$stateField . " FROM player", true);
    $data = ['_private' => [] ];
    foreach($states as $pId => $stateId){
      $state = self::getPrivateState($stateId, false);
      $method = $state['args'];
      $data['_private'][$pId] = [
        'state' => $state,
        'args' => self::getGame()->$method($pId),
      ];
    }

    return $data;
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

    // Call action if it exists
    if(isset($newState['action'])){
      $actionMethod = $newState['action'];
      self::getGame()->$actionMethod();
    }

    // Update state and args on UI using notification
    $player = Players::get($pId);
    $method = $newState['args'];
    self::getGame()->notifyPlayer($player->getId(), "newPrivateState", '', [
      'state' => $newState,
      'args' => self::getGame()->$method($player),
    ]);
  }
}



/*
 * Here is what you should do to make you game a singleton to access from other static modules
 *

 class welcometo extends Table {
   public static $instance = null;
   public function __construct(){
     parent::__construct();
     self::$instance = $this;
     ...
   }

   public static function get() {
     return self::$instance;
   }

   public static function getCurrentPId(){
     return self::getCurrentPlayerId();
   }
   ...
 }

 *
 * And here is the corresponding js code to handle these private states
 *

onEnteringState(stateName, args) {
  if(args.parallel){
    this.setupPrivateState(args.args._private.state, args.args._private.args);
    return;
  }
  ...
},

setupPrivateState(state, args){
  if(this.gamedatas.gamestate.parallel)
    delete this.gamedatas.gamestate.parallel;
  this.gamedatas.gamestate.name = state.name;
  this.gamedatas.gamestate.descriptionmyturn = state.descriptionmyturn;
  this.gamedatas.gamestate.possibleactions = state.possibleactions;
  this.gamedatas.gamestate.transitions = state.transitions;
  this.gamedatas.gamestate.args = args;
  this.updatePageTitle();
  this.onEnteringState(state.name, this.gamedatas.gamestate);
},

notif_newPrivateState(args){
  this.setupPrivateState(args.args.state, args.args.args);
},

*/
