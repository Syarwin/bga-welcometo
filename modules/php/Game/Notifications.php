<?php
namespace WTO\Game;
use welcometo;

class Notifications
{
  protected static function notifyAll($name, $msg, $data){
    welcometo::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($pId, $name, $msg, $data){
    welcometo::get()->notifyPlayer($pId, $name, $msg, $data);
  }

  public static function newCards($pId, $cards){
    if(is_null($pId)){
      self::notifyAll('newCards', '', [
        'cards' => $cards,
      ]);
    } else {
      self::notify($pId, 'newCards', '', [
        'cards' => $cards,
      ]);
    }
  }


  public static function writeNumber($player, $house){
    self::notify($player->getId(), 'writeNumber', '', [
      'house' => $house,
    ]);
  }

  public static function addScribble($player, $scribble){
    self::notify($player->getId(), 'addScribble', '', [
      'scribble' => $scribble,
    ]);
  }


  public static function clearTurn($player){
    self::notify($player->getId(), 'clearTurn', '', [
      'turn' => Globals::getCurrentTurn(),
    ]);
  }

}

?>
