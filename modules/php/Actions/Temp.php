<?php
namespace WTO\Actions;
use \WTO\Scribbles;
use \WTO\Game\Globals;

/*
 * Temp : manage everything related to temp agency
 */
class Temp extends Zone
{
  protected static $type = "score-temp";
  protected static $dim = 1;
  protected static $cols = 11;

  protected static $scores = [7,4,1];
  protected static $ordering = null;
  protected static function computeCounters(){
    $tempCounters = [];
    $scribbles = Scribbles::getInLocationQ(["%", "score-temp", "%"])->where('turn', '<', Globals::getCurrentTurn())->get(false)->toArray();
    foreach($scribbles as $scribble){
      if(!isset($tempCounters[$scribble['pId']]))
        $tempCounters[$scribble['pId']] = 0;
      $tempCounters[$scribble['pId']]++;
    }
    return $tempCounters;
  }
  protected static function computeOrdering($forceReload = false)
  {
    if(!$forceReload && !is_null(self::$ordering))
      return self::$ordering;

    // Count the number of scribbles of each players
    $tempCounters = self::computeCounters();

    $values = array_unique(array_values($tempCounters));
    rsort($values);
    array_push($values, -1,-1,-1); // Just to make sure we won't have any out of bound index
    $order = [
      [], [], []
    ];
    foreach($tempCounters as $pId => $counter){
      if($counter == $values[0]) array_push($order[0], $pId);
      if($counter == $values[1]) array_push($order[1], $pId);
      if($counter == $values[2]) array_push($order[2], $pId);
    }

    self::$ordering = $order;
  }


  public function getScores()
  {
    self::computeOrdering();
    return self::$ordering;
  }

  public function getScore($player)
  {
    if(Globals::isSolo())
      return self::getScoreSolo($player);

    $ordering = self::getScores();
    $score = 0;
    for($i = 0; $i < 3; $i++){
      if(\in_array($player->getId(), $ordering[$i]))
        $score = self::$scores[$i];
    }

    return [ 'temp-total' => $score];
  }

  public function getScoreSolo($player){
    $tempCounters = self::computeCounters();
    $pId = $player->getId();
    $score =  (isset($tempCounters[$pId]) && $tempCounters[$pId] >= 6)? 7 : 0 ;
    return [ 'temp-total' => $score];
  }
}
