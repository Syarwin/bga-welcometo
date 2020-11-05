<?php
namespace WTO;
use welcometo;

class Notifications
{
  protected static function notifyAll($name, $msg, $data){
    welcometo::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($pId, $name, $msg, $data){
    welcometo::get()->notifyPlayer($pId, $name, $msg, $data);
  }


  public static function writeNumber($player, $house){
    self::notify($player->getId(), 'writeNumber', '', [
      'house' => $house,
    ]);
  }


  public static function clearTurn($player){
    self::notify($player->getId(), 'clearTurn', '', [
      'turn' => Globals::getCurrentTurn(),
    ]);
  }

}

?>
