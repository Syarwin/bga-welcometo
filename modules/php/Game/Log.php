<?php
namespace WTO\Game;
use welcometo;

/*
 * Log: a class that allows to log some actions
 *   and then fetch these actions latter
 */
class Log extends \WTO\Helpers\DB_Manager
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
      'moveId' => $row['move_id'],
      'arg' => json_decode($row['action_arg'], true),
    ];
  }

  /*
   * Utils : where filter with player and current turn
   */
  private function getFilteredQuery($pId){
    return self::DB()->where('player_id', $pId)->where('turn', Globals::getCurrentTurn() )->orderBy("log_id", "DESC");
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
    $moveId = self::getUniqueValueFromDB("SELECT global_value FROM global WHERE global_id = 3");
    $turn = Globals::getCurrentTurn();
    $actionArgs = json_encode($args);
    self::DB()->insert([
      'turn' => $turn,
      'player_id' => $pId,
      'action' => $action,
      'action_arg' => $actionArgs,
      'move_id' => $moveId,
    ]);
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

  public static function getLastAction($action, $pId, $limit = 1)
  {
    return self::getFilteredQuery($pId)->where('action', $action)->limit($limit)->get($limit == 1);
  }


  /*
   * getCancelMoveIds : get all cancelled move IDs from BGA gamelog, used for styling the notifications on page reload
   */
  public function getCancelMoveIds()
  {
    $moveIds = self::getObjectListFromDb("SELECT `gamelog_move_id` FROM gamelog WHERE `cancel` = 1 ORDER BY 1", true);
    return array_map('intval', $moveIds);
  }



/////////////////////////////////
/////////////////////////////////
//////////   Setters   //////////
/////////////////////////////////
/////////////////////////////////
  public static function clearTurn($pId)
  {
    // Cancel the game notifications
    $moveIds = [];
    foreach(self::getFilteredQuery($pId)->get(false) as $action){
      if(!is_null($action["moveId"])){
        array_push($moveIds, $action["moveId"]);
      }
    }

    if (!empty($moveIds)) {
      self::DbQuery("UPDATE gamelog SET `cancel` = 1 WHERE `gamelog_move_id` IN (" . implode(',', $moveIds) . ")");
    }

    // Clear the log
    self::getFilteredQuery($pId)->delete()->run();

    return $moveIds;
  }
}
