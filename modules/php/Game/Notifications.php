<?php
namespace WTO\Game;
use welcometo;
use \WTO\PlanCards;

class Notifications
{
  protected static function notifyAll($name, $msg, $data){
    welcometo::get()->notifyAllPlayers($name, $msg, $data);
  }

  protected static function notify($pId, $name, $msg, $data){
    welcometo::get()->notifyPlayer($pId, $name, $msg, $data);
  }

  public static function newCards($pId, $cards){
    $data = [
      'cards' => $cards,
      'turn' => Globals::getCurrentTurn(),
    ];

    if(is_null($pId)){
      self::notifyAll('newCards', '', $data);
    } else {
      self::notify($pId, 'newCards', '', $data);
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

  public static function addMultipleScribbles($player, $scribbles){
    self::notify($player->getId(), 'addMultipleScribbles', '', [
      'scribbles' => $scribbles,
    ]);
  }

  public static function planScored($player, $planId, $validations){
    self::notify($player->getId(), "scorePlan", '', [
      'validation' => $validations[$player->getId()],
      'planId' => $planId
    ]);
  }


  public static function clearTurn($player){
    self::notify($player->getId(), 'clearTurn', '', [
      'turn' => Globals::getCurrentTurn(),
    ]);
  }


  public static function updateScores($player){
    self::notify($player->getId(), 'updateScores', '', [
      'scores' => $player->getScores(),
    ]);
  }

  public static function updatePlayersData(){
    self::notifyAll('updatePlayersData', '', [
      'players' => Players::getUiData(),
      'planValidations' => PlanCards::getCurrentValidations(),
    ]);
  }
}

?>
