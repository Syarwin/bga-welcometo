<?php
namespace WTO\Helpers;

/*
 * Zone : generic handler of given zone type
 */
class Zone
{
  // Need one or two params ? default is 2 : x,y
  protected static $dim = 2;
  // Size of the different zone, if 1d just an int
  protected static $cols = [];

  public function getOfPlayer($pId)
  {
    Scribbles::getInLocation([$pId, static::$type])->get();
  }

}
