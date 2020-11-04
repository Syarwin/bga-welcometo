<?php
namespace WTO;
use welcometo;

class Player extends Helpers\DB_Manager
{
  public function __construct($row)
  {
    $this->id = (int) $row['player_id'];
    $this->no = (int) $row['player_no'];
    $this->name = $row['player_name'];
    $this->color = $row['player_color'];
    $this->eliminated = $row['player_eliminated'] == 1;
    $this->zombie = $row['player_zombie'] == 1;
    $this->state = $row['player_state'];
  }

  private $id;
  private $no; // natural order
  private $name;
  private $color;
  private $eliminated = false;
  private $zombie = false;


  public function getId(){ return $this->id; }
  public function getNo(){ return $this->no; }
  public function getName(){ return $this->name; }
  public function getColor(){ return $this->color; }
  public function isEliminated(){ return $this->eliminated; }
  public function isZombie(){ return $this->zombie; }
  public function getState(){ return $this->state; }

  public function getUiData()
  {
    return [
      'id'        => $this->id,
      'no'        => $this->no,
      'name'      => $this->name,
      'color'     => $this->color,
    ];
  }


  public function getConstructionCards()
  {
    return ConstructionCards::getForPlayer($this->id);
  }

  public function hasSomethingToCancel()
  {
    return !empty(Log::getLastActions($this->id));
  }


  ///////////////////////////////
  //////// CHOOSE CARDS /////////
  ///////////////////////////////
  public function chooseCards($stack)
  {
    Log::insert($this->id, 'selectCard', $stack);
  }

  public function getSelectedCards()
  {
    $selectCardAction = Log::getLastAction('selectCard', $this->id);
    return is_null($selectCardAction)? null : $selectCardAction['arg'];
  }

  public function getCombination()
  {
    $selectedCards = $this->getSelectedCards();
    if(is_null($selectedCards))
      throw new \BgaVisibleSystemException("Trying to fetch the combination of a player who haven't choose the construction cards yet");

    return ConstructionCards::getCombination($this->id, $selectedCards);
  }


  ///////////////////////////////
  //////// WRITE NUMBER /////////
  ///////////////////////////////
  public function getAvailableNumbers()
  {
    return $this->getAvailableNumbersOfCombination($this->getCombination());
  }

  public function getAvailableNumbersForBis()
  {
    $result = [];
    for($i = 13; $i <= 13; $i++){
//    for($i = 0; $i <= 17; $i++){
      $houses = $this->getAvailableHousesForNumber($i, true);

      if(!empty($houses))
        $result[$i] = $houses;
    }
    return $result;
  }

  public function getAvailableNumbersOfCombination($combination)
  {
    // Unless the action is temporary agent, a combination is uniquely associated to a number
    $numbers = [ $combination["number"] ];

    // For temporary agent, we can do -2, -1, +1, +2
    if($combination["action"] == TEMP){
      $modifiers = [-2, -1, 1, 2];
      foreach($modifiers as $dx){
        $n = $combination["number"] + $dx;
        if($n < 1 || $n > 15)
          continue;

        array_push($numbers, $n);
      }
    }

    // For each number, compute list of houses where we can write the number
    $result = [];
    foreach($numbers as $number){
      $houses = $this->getAvailableHousesForNumber($number);
      if(!empty($houses))
        $result[$number] = $houses;
    }
    return $result;
  }

  public function getAvailableHousesForNumber($number, $isBis = false){
    return Houses::getAvailableLocations($this->id, $number, $isBis);
  }


  public function writeNumber($number, $pos, $isBis = false)
  {
    Houses::add($this->id, $number, $pos, $isBis);
  }

}
