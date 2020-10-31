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
      'id' => (int) $row['id'],
      'pId' => (int) $row['player_id'],
      'turn' => (int) $row['turn'],
      'action' => $row['action'],
      'arg' => json_decode($row['action_arg'], true),
    ];
  }

////////////////////////////////
////////////////////////////////
//////////   Adders   //////////
////////////////////////////////
////////////////////////////////

  /*
   * insert: add a new log entry
   * params:
   *   - int $pid: the player who is making the action
   *   - string $action : the name of the action
   *   - array $args : action arguments
   */
  public static function insert($pId, $action, $args = [])
  {
    $turn = Globals::getCurrentTurn();
    $actionArgs = json_encode($args);
    self::DB()->insert(['turn' => $turn, 'player_id' => $pId, 'action' => $action, 'action_arg' => $actionArgs]);
  }


/////////////////////////////////
/////////////////////////////////
//////////   Getters   //////////
/////////////////////////////////
/////////////////////////////////
  private static function getLastActionsQuery($pId)
  {
    return self::DB()->where('player_id', $pId)->where('turn', Globals::getCurrentTurn() );
  }


  public static function getLastActions($pId)
  {
    return self::getLastActionsQuery($pId)->get();
  }

  public static function getLastAction($action, $pId)
  {
    return self::getLastActionsQuery($pId)->where('action', $action)->limit(1)->get();
  }
}
