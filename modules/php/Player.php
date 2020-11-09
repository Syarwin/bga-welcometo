<?php
namespace WTO;
use WTO\Game\Log;
use WTO\Game\Notifications;

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


  /////////////////////////////////
  /////////////////////////////////
  //////////   Getters   //////////
  /////////////////////////////////
  /////////////////////////////////

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


  /*
   * Boolean value needed to know if we display the "restart turn" button
   */
  public function hasSomethingToCancel()
  {
    return !empty(Log::getLastActions($this->id));
  }

  /*
   * Return the constructions for player (which can be player-specific in the expert variant)
   */
  public function getConstructionCards()
  {
    return ConstructionCards::getForPlayer($this->id);
  }


  /*
   * Return either the selected stacks (of construction cards) if any, or null
   */
  public function getSelectedCards()
  {
    $selectCardAction = Log::getLastAction('selectCard', $this->id);
    return is_null($selectCardAction)? null : $selectCardAction['arg'];
  }


  /*
   * Allow to format the selected stacks (getter defined below)
   *   into a combination [number, action]
   */
  public function getCombination()
  {
    $selectedCards = $this->getSelectedCards();
    if(is_null($selectedCards))
      throw new \BgaVisibleSystemException("Trying to fetch the combination of a player who haven't choose the construction cards yet");

    return ConstructionCards::getCombination($this->id, $selectedCards);
  }


  /*
   * Get the house written that turn if any
   */
   public function getLastHouse()
   {
     return Houses::getLast($this->id);
   }


  ///////////////////////////////
  //////// CHOOSE CARDS /////////
  ///////////////////////////////
  public function chooseCards($stack)
  {
    Log::insert($this->id, 'selectCard', $stack);
  }




/////////////////////////////////
/////////////////////////////////
//////////   Setters   //////////
/////////////////////////////////
/////////////////////////////////

  public function restartTurn()
  {
    Log::clearTurn($this->id);
    Houses::clearTurn($this->id);
    Scribbles::clearTurn($this->id);
    Notifications::clearTurn($this);
  }


  ///////////////////////////////
  //////// WRITE NUMBER /////////
  ///////////////////////////////
  public function getAvailableStacks()
  {
    $combinations = ConstructionCards::getPossibleCombinations($this->id);
    $result = [];
    foreach($combinations as $combination){
      if(!empty($this->getAvailableNumbersOfCombination($combination)))
        array_push($result, $combination['stacks']);
    }
    return $result;
  }

  public function getAvailableNumbers()
  {
    return $this->getAvailableNumbersOfCombination($this->getCombination());
  }

  public function getAvailableNumbersForBis()
  {
    $result = [];
    for($i = 0; $i <= 17; $i++){
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
    return $isBis?
      Houses::getAvailableLocationsForBis($this->id, $number)
     :Houses::getAvailableLocations($this->id, $number);
  }


  public function writeNumber($number, $pos, $isBis = false)
  {
    $house = Houses::add($this->id, $number, $pos, $isBis);
    Notifications::writeNumber($this, $house);
  }


  public function scribbleZone($zone)
  {
    $stateId = $this->getState();
    $locations = [
      ST_ACTION_ESTATE => "score-estate",
      ST_ACTION_TEMP   => "score-temp",
      ST_ACTION_BIS    => "score-bis",
      ST_ACTION_POOL   => "score-pool",
      ST_ACTION_PARK   => "park",
    ];

    // TODO : add sanity checks
    $scribble = Scribbles::add($this->id, $locations[$stateId], $zone);
    Notifications::addScribble($this, $scribble);

    // If building a pool, add another scribble on the pool itself
    if($stateId == ST_ACTION_POOL){
      $house = $this->getLastHouse();
      $scribble = Scribbles::add($this->id, "pool", [ $house['x'], $house['y'] ]);
      Notifications::addScribble($this, $scribble);
    }
  }
}
