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
  protected static $customFields = ['player_id', 'turn'];
  protected static function cast($card){
    return $card;
  }

}
