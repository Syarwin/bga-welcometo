<?php

namespace WTO;
use welcometo;
/*
 * Globals
 */
class Globals
{
  public static function isAdvanced()
  {
    return boolval(welcometo::get()->getGameStateValue("optionAdvanced"));
  }

  public static function isExpert()
  {
    return boolval(welcometo::get()->getGameStateValue("optionExpert"));
  }

  public static function isSolo()
  {
    return boolval(Players::count() == 1);
  }

  public static function isStandard()
  {
    return !self::isExpert() && !self::isSolo();
  }

  public static function getCurrentTurn()
  {
    return (int) welcometo::get()->getGameStateValue('currentTurn');
  }
}
