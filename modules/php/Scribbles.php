<?php
namespace WTO;
use WTO\Game\Globals;
use WTO\Game\Players;

/*
 * Scribbles
 */
class Scribbles extends Helpers\Pieces
{
  protected static $table = "scribbles";
	protected static $prefix = "scribble_";
  protected static $customFields = ['turn'];
  protected static function cast($scribble){
    $data = explode("_", $scribble['location']);
    return [
      'id' => $scribble['id'],
      'pId' => $data[0],
      'type' => $data[1],
      'x' => $data[2] ?? null,
      'y' => $data[3] ?? null,
      'turn' => $scribble['turn'],
    ];
  }

  public function getOfPlayer($pId)
  {
    $query = self::getInLocationQ([$pId, "%"]);
    // Filter out the scribbles of current turn if not current player
    if(Players::getCurrentId() != $pId)
      $query = $query->where('turn', '<', Globals::getCurrentTurn());

    return $query->get(false)->toArray();
  }

  public function hasScribbleSomething($pId)
  {
    return self::getInLocationQ([$pId, "%"])->where('turn', Globals::getCurrentTurn())->count() > 0;
  }


  /*
   * clearTurn : remove all houses written by player during this turn
   */
  public function clearTurn($pId)
  {
    self::getInLocationQ([$pId, "%"])->where('turn', Globals::getCurrentTurn() )->delete()->run();
  }


  /*
   * Add a new scribble
   */
  public static function add($pId, $type, $zone)
  {
    $location = array_merge([$pId, $type], $zone);
    // Sanity check : already exists ?
    $scribble = self::getTopOf($location);
    if(!is_null($scribble))
      return false;

    $id = self::create([ [
      'location' => $location,
      'turn' => Globals::getCurrentTurn(),
    ] ]);

    return self::get($id);
  }

}
