<?php
namespace WTO\Game;
use \WTO\Houses;
use \WTO\Scribbles;
use welcometo;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */
class Players extends \WTO\Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
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

    $pIds = array_keys($players);

    if(false){
    Houses::add($pIds[0], 2, [0,0], false);
    Houses::add($pIds[0], 2, [0,1], false);
    Houses::add($pIds[0], 3, [0,2], false);
    Houses::add($pIds[0], 5, [0,3], false);
    Houses::add($pIds[0], 7, [0,4], false);
    Houses::add($pIds[0], 8, [0,5], false);
    Houses::add($pIds[0], 11, [0,6], false);
    Houses::add($pIds[0], 12, [0,7], false);
    Houses::add($pIds[0], 13, [0,8], false);
    Houses::add($pIds[0], 14, [0,9], false);


    Houses::add($pIds[0], 1, [1,0], false);
    Houses::add($pIds[0], 2, [1,1], false);
    Houses::add($pIds[0], 3, [1,2], false);

    Houses::add($pIds[0], 4, [1,3], false);
    Houses::add($pIds[0], 7, [1,4], false);
    Houses::add($pIds[0], 8, [1,5], false);
    Houses::add($pIds[0], 9, [1,6], false);
    Houses::add($pIds[0], 10, [1,7], false);
    Houses::add($pIds[0], 11, [1,8], false);
    Houses::add($pIds[0], 12, [1,9], false);
    Houses::add($pIds[0], 13, [1,10], false);

  Scribbles::add($pIds[0], 'pool', [1,0]);
  Scribbles::add($pIds[0], 'pool', [1,3]);
  Scribbles::add($pIds[0], 'pool', [1,7]);



    Houses::add($pIds[0], 1, [2,0], false);
    Houses::add($pIds[0], 3, [2,1], false);
    Scribbles::add($pIds[0], 'pool', [2,1]);
    Houses::add($pIds[0], 4, [2,2], false);
    Houses::add($pIds[0], 5, [2,3], true);
    Houses::add($pIds[0], ROUNDABOUT, [2,4], true);
    Houses::add($pIds[0], 8, [2,5], false);
    Houses::add($pIds[0], 9, [2,6], true);
    Scribbles::add($pIds[0], 'pool', [2,6]);
    Houses::add($pIds[0], 11, [2,7], true);
//    Houses::add($pIds[0], 12, [2,8], false);
    Houses::add($pIds[0], 13, [2,9], true);
    Houses::add($pIds[0], 14, [2,10], false);
    Scribbles::add($pIds[0], 'pool', [2,10]);
    Houses::add($pIds[0], 15, [2,11], false);

    Scribbles::add($pIds[0], 'score-estate', [2,0]);
    Scribbles::add($pIds[0], 'score-estate', [2,1]);
    Scribbles::add($pIds[0], 'score-estate', [3,0]);


    Scribbles::add($pIds[0], 'estate-fence', [0,0]);
    Scribbles::add($pIds[0], 'estate-fence', [0,2]);
    Scribbles::add($pIds[0], 'estate-fence', [0,5]);
    Scribbles::add($pIds[0], 'estate-fence', [1,3]);
    Scribbles::add($pIds[0], 'estate-fence', [1,6]);
    Scribbles::add($pIds[0], 'estate-fence', [2,3]);
    Scribbles::add($pIds[0], 'estate-fence', [2,4]);
    Scribbles::add($pIds[0], 'estate-fence', [2,7]);


    Scribbles::add($pIds[0], 'park', [0,0]);
    Scribbles::add($pIds[0], 'park', [0,1]);
    Scribbles::add($pIds[0], 'park', [0,2]);

    Scribbles::add($pIds[0], 'park', [1,0]);

    Scribbles::add($pIds[0], 'park', [2,0]);
    Scribbles::add($pIds[0], 'park', [2,1]);
    Scribbles::add($pIds[0], 'park', [2,2]);
    Scribbles::add($pIds[0], 'park', [2,3]);
    Scribbles::add($pIds[0], 'park', [2,4]);

    Scribbles::add($pIds[0], 'score-pool', [0]);
    Scribbles::add($pIds[0], 'score-pool', [1]);
    Scribbles::add($pIds[0], 'score-pool', [2]);
    Scribbles::add($pIds[0], 'score-pool', [3]);
    Scribbles::add($pIds[0], 'score-pool', [4]);

    Scribbles::add($pIds[0], 'score-temp', [0]);
    Scribbles::add($pIds[0], 'score-temp', [1]);
    Scribbles::add($pIds[0], 'score-temp', [2]);
    Scribbles::add($pIds[0], 'score-temp', [3]);
    Scribbles::add($pIds[0], 'score-temp', [4]);
    Scribbles::add($pIds[0], 'score-temp', [5]);
    Scribbles::add($pIds[0], 'score-temp', [6]);

    Scribbles::add($pIds[0], 'score-bis', [0]);
    Scribbles::add($pIds[0], 'score-bis', [1]);
    Scribbles::add($pIds[0], 'score-bis', [2]);
    Scribbles::add($pIds[0], 'score-bis', [3]);

//    Scribbles::add($pIds[1], 'score-temp', [0]);
    }
  }

  public function getActiveId()
  {
    return welcometo::get()->getActivePlayerId();
  }

  public function getCurrentId()
  {
    return welcometo::get()->getCurrentPId();
  }

  public function getAll(){
    return self::DB()->get(false);
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

  public function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public function getNextId($player)
  {
    $table = welcometo::get()->getNextPlayerTable();
    return $table[$player->getId()];
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
    return self::getAll()->assocMap(function($player){ return $player->getUiData(); });
  }
}
