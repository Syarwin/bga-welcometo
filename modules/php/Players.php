<?php
namespace WTO;
use welcometo;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */
class Players extends Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  protected static $associative = false;
  protected static function cast($row)
  {
    return new \WTO\Player($row);
  }


  public function setupNewGame($players)
  {
    // Create players
    self::DB()->delete();

    $gameInfos = welcometo::get()->getGameinfos();
    $colors = $gameInfos['player_colors'];
    $query = self::DB()->multipleInsert(['player_id', 'player_color', 'player_canal', 'player_name', 'player_avatar']);
    $values = [];
    foreach ($players as $pId => $player) {
      $color = array_shift($colors);
      $values[] = [ $pId, $color, $player['player_canal'], $player['player_name'], $player['player_avatar'] ];
    }
    $query->values($values);
    welcometo::get()->reattributeColorsBasedOnPreferences($players, $gameInfos['player_colors']);
    welcometo::get()->reloadPlayersBasicInfos();
  }

  public function getActiveId()
  {
    return welcometo::get()->getActivePlayerId();
  }

  public function getAll(){
    return self::DB()->get();
  }

  /*
   * get : returns the Player object for the given player ID
   */
  public function get($pId = null)
  {
    $pId = $pId ?: self::getActiveId();
    return self::DB()->where($pId)->get();
  }

  public function getActive()
  {
    return self::get();
  }

  /*
   * Return the number of players
   */
  public function count()
  {
    return self::DB()->count();
  }


  /*
   * getUiData : get all ui data of all players : id, no, name, team, color, powers list, farmers
   */
  public function getUiData()
  {
    $ui = [];
    foreach (self::getAll() as $player)
       $ui[$player->getId()] = $player->getUiData();

    return $ui;
  }
}
