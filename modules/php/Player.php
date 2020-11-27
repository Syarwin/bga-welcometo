<?php
namespace WTO;
use WTO\Game\Log;
use WTO\Game\Notifications;
use WTO\Game\Globals;
use WTO\Game\Players;

use \WTO\Actions\RealEstate;
use \WTO\Actions\Park;
use \WTO\Actions\Temp;
use \WTO\Actions\Bis;
use \WTO\Actions\Pool;
use \WTO\Actions\Surveyor;
use \WTO\Actions\PermitRefusal;
use \WTO\Actions\Roundabout;

class Player extends Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  public function __construct($row)
  {
    $this->id = (int) $row['player_id'];
    $this->no = (int) $row['player_no'];
    $this->name = $row['player_name'];
    $this->color = $row['player_color'];
    $this->score = $row['player_score'];
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
  private $score;


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
      'score'     => $this->score,
      'scoreSheet' => $this->getScoreSheet(),
    ];
  }

  public function getScoreSheet()
  {
    return [
      'scores' => $this->getScores(),
      'houses' => Houses::getOfPlayer($this->id),
      'scribbles' => Scribbles::getOfPlayer($this->id),
    ];
  }


  public function getScores($computeTotal = true)
  {
    $data = \array_merge(
      Park::getScore($this),
      Pool::getScore($this),
      Temp::getScore($this),
      Bis::getScore($this),
      RealEstate::getScore($this),
      PlanCards::getScore($this),
      PermitRefusal::getScore($this),
      Roundabout::getScore($this)
    );
    $data['other-total'] = $data['permit-total'] + $data['roundabout-total'];

    if($computeTotal)
      $data['total'] = $this->computeScore();
    return $data;
  }

  public function computeScore()
  {
    $scores = $this->getScores(false);
    $total = $scores['plan-total'] + $scores['park-total'] + $scores['pool-total'] + $scores['temp-total']
      + $scores['estate-total-0'] + $scores['estate-total-1'] + $scores['estate-total-2']
      + $scores['estate-total-3'] + $scores['estate-total-4'] + $scores['estate-total-5']
      - $scores['bis-total'] - $scores['permit-total'] - $scores['roundabout-total'];
    return $total;
  }

  public function storeScore()
  {
    $score = $this->computeScore();
    self::DB()->update(['player_score' => $score])->run($this->id);
    return $score;
  }


  public function updateScores()
  {
    Notifications::updateScores($this);
  }


  public function getEstates($evenIfUsedInPlan = true)
  {
    return RealEstate::getEstates($this, $evenIfUsedInPlan);
  }

  /*
   * Boolean value needed to know if we display the "restart turn" button
   */
  public function hasSomethingToCancel()
  {
    return !empty(Log::getLastActions($this->id)) || Scribbles::hasScribbleSomething($this->id);
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
   * Return either the selected plans if any, or null
   */
  public function getSelectedPlans()
  {
    return Log::getLastAction('selectPlan', $this->id, 3)->map(function($action){ return $action['arg']; });
  }

  /*
   * Return the currently in validation plan
   */
  public function getCurrentPlan()
  {
    $selectedPlanAction = Log::getLastAction('selectPlan', $this->id);
    if(is_null($selectedPlanAction))
      throw new \BgaVisibleSystemException("No current plan selected");

    return PlanCards::get($selectedPlanAction['arg']);
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


/////////////////////////////////
/////////////////////////////////
///////// START OF TURN /////////
/////////////////////////////////
/////////////////////////////////

  // EXPERT MODE : give the unused card to next player
  public function giveThirdCardToNextPlayer()
  {
      $stacks = $this->getSelectedCards();
      $pId = Players::getNextId($this);
      ConstructionCards::prepareCardsForNextTurn($this->id, $stacks, $pId);
  }

  // Restart the turn by clearing all log, houses, scribbles.
  public function restartTurn()
  {
    Log::clearTurn($this->id);
    Houses::clearTurn($this->id);
    Scribbles::clearTurn($this->id);
    PlanCards::clearTurn($this->id);
    Notifications::clearTurn($this);
  }


  ///////////////////////////////
  //////// CHOOSE CARDS /////////
  ///////////////////////////////

  /*
   * Given a number/action combination (as assoc array), compute the set of writtable numbers on the sheet
   */
  public function getAvailableNumbersOfCombination($combination)
  {
    // Unless the action is temporary agent, a combination is uniquely associated to a number
    $numbers = [ $combination["number"] ];

    // For temporary agent, we can do -2, -1, +1, +2
    if($combination["action"] == TEMP){
      $modifiers = [-2, -1, 1, 2];
      foreach($modifiers as $dx){
        $n = $combination["number"] + $dx;
        if($n < 0 || $n > 17)
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


  /*
   *  Return the set of possible number to write given current selected combination
   */
  public function getAvailableNumbers()
  {
    return $this->getAvailableNumbersOfCombination($this->getCombination());
  }


  /*
   * Using function above, we can return the stack combinations that leads to at least one writtable number
   */
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


  /*
   * Tag the selected cards
   */
  public function chooseCards($stack)
  {
    Log::insert($this->id, 'selectCard', $stack);
  }


  /////////////////////////////////
  ///////// WRITE NUMBER //////////
  /////////////////////////////////
  public function getStreets(){
    return Houses::getStreets($this);
  }

  /*
   * Given a number, return the list of possible houses to be written on it
   */
  public function getAvailableHousesForNumber($number){
    return Houses::getAvailableLocations($this, $number);
  }


  /*
   * Write the number on the house
   */
  public function writeNumber($number, $pos, $isBis = false)
  {
    $house = Houses::add($this->id, $number, $pos, $isBis);
    Notifications::writeNumber($this, $house);
  }



  /////////////////////////////////
  /////////// ACTIONS  ////////////
  /////////////////////////////////
  /*
   * Generic zone scribbling that handle almost all actions
   */
   public function scribbleZone($zone, $type = null)
   {
     // Compute the name of the zone depending on the state
     $stateId = $this->getState();
     $locations = [
       ST_CHOOSE_CARDS  => "permit-refusal",
       ST_ACTION_SURVEYOR => "estate-fence",
       ST_ACTION_ESTATE => "score-estate",
       ST_ACTION_TEMP   => "score-temp",
       ST_ACTION_BIS    => "score-bis",
       ST_ACTION_POOL   => "score-pool",
       ST_ACTION_PARK   => "park",

       ST_ROUNDABOUT => "score-roundabout",
     ];
     $type = $type ?? $locations[$stateId];


     $scribble = Scribbles::add($this->id, $type, $zone);
     if($scribble !== false){
       Notifications::addScribble($this, $scribble);
     }

     // If building a pool, add another scribble on the pool itself
     if($stateId == ST_ACTION_POOL){
       $house = $this->getLastHouse();
       $scribble = Scribbles::add($this->id, "pool", [ $house['x'], $house['y'] ]);
       Notifications::addScribble($this, $scribble);
     }
   }


  /*
   * Computing available number for bis action
   */
  public function getAvailableNumbersForBis()
  {
    $result = [];
    for($i = 0; $i <= 17; $i++){
      $houses = Houses::getAvailableLocationsForBis($this, $i);

      if(!empty($houses))
        $result[$i] = $houses;
    }
    return $result;

    return $houses;
  }




/////////////////////////////////
/////////////////////////////////
/////////// CITY PLANS //////////
/////////////////////////////////
/////////////////////////////////

  /*
   *  Return the set of scorable plans
   */
  public function getScorablePlans()
  {
    $res = [];
    foreach(PlanCards::getCurrent() as $plan){
      if($plan->canBeScored($this))
        array_push($res, $plan->getId());
    }
    return $res;
  }


  /*
   * Tag the selected plan
   */
  public function choosePlan($planId)
  {
    Log::insert($this->id, 'selectPlan', $planId);
  }
}
