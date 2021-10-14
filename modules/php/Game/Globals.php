<?php
namespace WTO\Game;
use welcometo;
/*
 * Globals
 */
class Globals extends \APP_DbObject
{
  public static function getMoveId()
  {
    return self::getUniqueValueFromDB("SELECT global_value FROM global WHERE global_id = 3");
  }


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

  public static function getBoard()
  {
    return intval(welcometo::get()->getGameStateValue("optionBoard"));
  }
  public static function isIceCream()
  {
    return self::getBoard() == OPTION_BOARD_ICE_CREAM;
  }

  public static function isStandard()
  {
    return !self::isExpert() && !self::isSolo();
  }


  public static function getOptions()
  {
    return [
      "advanced" => self::isAdvanced(),
      "expert" => self::isExpert(),
      "board" => self::getBoard(),

      // Not 100% needed as this can recomputed on client side, but who cares ?
      "solo" => self::isSolo(),
      "standard" => self::isStandard(),
    ];
  }


  public static function getCurrentTurn()
  {
    return (int) welcometo::get()->getGameStateValue('currentTurn');
  }
}
