<?php
namespace WTO;
use welcometo;
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
      'y' => $data[3] ?? nul,
      'turn' => $scribble['turn'],
    ];
  }

  public function getOfPlayer($pId)
  {
    return self::getInLocation([$pId, "%"])->toArray();
  }


  /*
   * Add a new scribble
   */
  public static function add($pId, $type, $zone)
  {
    $location = array_merge([$pId, $type], $zone);
    $id = self::create([ [
      'location' => $location,
      'turn' => Globals::getCurrentTurn(),
    ] ]);

    return self::get($id);
  }

}
