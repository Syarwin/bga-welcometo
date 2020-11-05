<?php

namespace WTO;
/*
 * Log: a class that allows to log some actions
 *   and then fetch these actions latter
 */
class Log extends Helpers\DB_Manager
{
  protected static $table = 'log';
  protected static $primary = 'log_id';
  protected static $associative = false;
  protected static function cast($row)
  {
    return [
      'id' => (int) $row['log_id'],
      'pId' => (int) $row['player_id'],
      'turn' => (int) $row['turn'],
      'action' => $row['action'],
      'arg' => json_decode($row['action_arg'], true),
    ];
  }

  /*
   * Utils : where filter with player and current turn
   */
  private function getFilteredQuery($pId){
    return self::DB()->where('player_id', $pId)->where('turn', Globals::getCurrentTurn() );
  }

////////////////////////////////
////////////////////////////////
//////////   Adders   //////////
////////////////////////////////
////////////////////////////////

  /*
   * insert: add a new log entry
   * params:
   *   - mixed $player : either the id or an object of the player who is making the action
   *   - string $action : the name of the action
   *   - array $args : action arguments
   */
  public static function insert($player, $action, $args = [])
  {
    $pId = is_integer($player)? $player : $player->getId();
    $turn = Globals::getCurrentTurn();
    $actionArgs = json_encode($args);
    self::DB()->insert(['turn' => $turn, 'player_id' => $pId, 'action' => $action, 'action_arg' => $actionArgs]);
  }


/////////////////////////////////
/////////////////////////////////
//////////   Getters   //////////
/////////////////////////////////
/////////////////////////////////
  public static function getLastActions($pId)
  {
    return self::getFilteredQuery($pId)->get();
  }

  public static function getLastAction($action, $pId)
  {
    return self::getFilteredQuery($pId)->where('action', $action)->limit(1)->get(true);
  }


/////////////////////////////////
/////////////////////////////////
//////////   Setters   //////////
/////////////////////////////////
/////////////////////////////////
  public static function clearTurn($pId)
  {
    self::getFilteredQuery($pId)->delete()->run();
  }
}
