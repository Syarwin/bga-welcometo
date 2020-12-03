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

  public static function message($txt, $args = []){
    self::notifyAll('message', $txt, $args);
  }

  public static function messageTo($player, $txt, $args = []){
    $pId = ($player instanceof \WTO\Player)? $player->getId() : $player;
    self::notify($pId, 'message', $txt, $args);
  }



  public static function soloCard(){
    self::notifyAll('soloCard', clienttranslate('The solo card was drawn'), []);
  }

  public static function newCards($pId, $cards){
    $data = [
      'cards' => $cards,
      'turn' => Globals::getCurrentTurn(),
      'cardsLeft' => \WTO\ConstructionCards::getInLocation('deck')->count(),
    ];

    $msg = clienttranslate("New cards are drawn.");
    if(is_null($pId)){
      self::notifyAll('newCards', $msg, $data);
    } else {
      self::notify($pId, 'newCards', $msg, $data);
    }
  }

  public static function reshuffle(){
    self::notifyAll('reshuffle', clienttranslate("First player who completed a goal asked for reshuffling the cards."), []);
  }


  public static function giveThirdCardToNextPlayer($pId, $stackId, $nextPId){
    $msg = clienttranslate("Giving remeaning card to next player");
    self::notify($pId, 'giveCard', $msg, [
      'stack' => $stackId,
      'pId' => $nextPId,
    ]);
  }

  public static function chooseCards($player){
    $combination = $player->getCombination();
    self::messageTo($player, clienttranslate('You choose the combination : ${number} & ${action}.'), [
      'i18n' => ['action'],
      'action' => ACTION_NAMES[$combination["action"]],
      'number' => $combination["number"],
    ]);
  }

  public static function writeNumber($player, $house){
    $msgs = [];
    if($house['number'] == ROUNDABOUT){
      $msgs = [
        clienttranslate("You build a roundabout in the top street."),
        clienttranslate("You build a roundabout in the middle street."),
        clienttranslate("You build a roundabout in the bottom street."),
      ];
    } else {
      $msgs = [
        clienttranslate('You build a n°${number} in the top street.'),
        clienttranslate('You build a n°${number} in the middle street.'),
        clienttranslate('You build a n°${number} in the bottom street.'),
      ];
    }
    $msg = $msgs[$house['x']];

    self::notify($player->getId(), 'writeNumber', $msg, [
      'house' => $house,
      'number'=> $house['number'] . ($house['isBis']? "bis" : ""),
    ]);
  }

  public static function addScribble($player, $scribble, $silent){
    $msg = '';
    if(!$silent){
      $msgs = [
        "permit-refusal" => clienttranslate("You cannot build and hence get a building permit refusal."),
        "estate-fence" => clienttranslate("You build a fence."),
        "score-estate" => clienttranslate('You increase the value of completed estates of size ${size}'),
        "score-temp" => clienttranslate("You hire a temp worker."),
        "score-bis" => "",
        "score-pool" => clienttranslate("You build a pool in the house."),
        "park" => clienttranslate("You build a park in the street."),
        "score-roundabout" => "",
      ];
      $msg = $msgs[$scribble['type']];
    }

    self::notify($player->getId(), 'addScribble', $msg, [
      'scribble' => $scribble,
      'size' => $scribble['type'] == "score-estate"? ($scribble['x'] + 1) : null,
    ]);
  }

  public static function addMultipleScribbles($player, $scribbles){
    self::notify($player->getId(), 'addMultipleScribbles', '', [
      'scribbles' => $scribbles,
    ]);
  }

  public static function planScored($player, $plan, $validations){
    $msg = clienttranslate('You validate plan n°${stack}');
    self::notify($player->getId(), "scorePlan", $msg, [
      'validation' => $validations[$player->getId()],
      'planId' => $plan->getId(),
      'stack' => $plan->getStack(),
    ]);
  }


  public static function clearTurn($player, $notifIds){
    self::notify($player->getId(), 'clearTurn', clienttranslate('You restart your turn'), [
      'turn' => Globals::getCurrentTurn(),
      'notifIds' => $notifIds,
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
      'turn' => Globals::getCurrentTurn() - 1, // Already incremented turn before calling this notif
    ]);
  }
}

?>
