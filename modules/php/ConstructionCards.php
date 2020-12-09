<?php
namespace WTO;
use WTO\Game\Globals;
use WTO\Game\Notifications;
use WTO\Game\Players;
use \WTO\Helpers\QueryBuilder;
use welcometo;

/*
 * Construction Cards
 */
class ConstructionCards extends Helpers\Pieces
{
  protected static $table = "construction_cards";
	protected static $prefix = "card_";
  protected static $customFields = ['number', 'action'];
  protected static $autoreshuffleCustom = ['deck' => 'discard'];
  protected static $autoreshuffle = true;
  protected static function cast($card){
    return $card;
  }

  protected static $actions = [SURVEYOR, POOL, TEMP, BIS, PARK, ESTATE];
  protected static $deck = [
    // SURVEYOR, POOL, TEMP, BIS, PARK, ESTATE
    1 =>  [   1,    0,    0,    0,    1,    1],
    2 =>  [   1,    0,    0,    0,    1,    1],
    3 =>  [   1,    1,    1,    1,    0,    0],
    4 =>  [   0,    1,    1,    1,    1,    1],
    5 =>  [   2,    0,    0,    0,    2,    2],
    6 =>  [   2,    1,    1,    1,    1,    1],
    7 =>  [   1,    1,    1,    1,    2,    2],
    8 =>  [   2,    1,    1,    1,    2,    2],
    9 =>  [   1,    1,    1,    1,    2,    2],
    10 => [   2,    1,    1,    1,    1,    1],
    11 => [   2,    0,    0,    0,    2,    2],
    12 => [   0,    1,    1,    1,    1,    1],
    13 => [   1,    1,    1,    1,    0,    0],
    14 => [   1,    0,    0,    0,    1,    1],
    15 => [   1,    0,    0,    0,    1,    1],
  ];


  //////////////////////////////////
  //////////////////////////////////
  ///////////// SETUP //////////////
  //////////////////////////////////
  //////////////////////////////////

  public function setupNewGame($players){
    $cards = [];
    foreach(self::$deck as $number => $nActions){
      foreach(self::$actions as $index => $action){
        $cards[] = [
          'number' => $number,
          'action' => $action,
          'nbr' => $nActions[$index]
        ];
      }
    }

    self::create($cards, 'deck');
    self::shuffle('deck');

    if(Globals::isStandard()){
      // Standard mode : set-up an initial card in each stack, new turn will bring the second one.
      self::draw();
    } else {
      if(Globals::isSolo()){
        // Solo mode setup : add the solo card on the bottom half
        self::soloSetupNewGame();
      }
      else if(Globals::isExpert()){
        // Expert mode : pre-draft one card per player
        foreach($players as $playerId => $t){
          self::pickForLocation(1, 'deck', "for_{$playerId}");
        }
      }
    }
  }


  public function soloSetupNewGame()
  {
    // Add the solo cards in the deck
    self::create([ ['number' => null, 'action' => SOLO, 'location' => 'deck'] ]);
    self::shuffle('deck');

    // Depending on its position, move it around
    $middle = 42;
    $card = self::DB()->where('action', SOLO)->get();
    if ($card['card_state'] >= $middle) {
      self::insertAt($card['card_id'], 'deck', $card['card_state'] - $middle);
    }
  }


  ////////////////////////////////////
  ////////////////////////////////////
  //////////// NEW TURN //////////////
  ////////////////////////////////////
  ////////////////////////////////////

  /*
   * Return the stacks depending on whether we are playing in expert mode or not
   */
  public function getStacks($playerId)
  {
    if(Globals::isExpert())
      return ["{$playerId}_stack_0", "{$playerId}_stack_1", "{$playerId}_stack_2"];
    else
      return ['stack_0', 'stack_1', 'stack_2'];
  }


  /*
   * Call the corresponding method either :
   *   - without arg is standard mode
   *   - once by player if non-standard mode
   */
  protected function callWithRightArguments($method){
    // Standard mode => draw 3 cards that are the same for all player
    if(Globals::isStandard())
      self::$method();
    // Non standard mode => draw 3 cards for each player
    else {
      foreach(Players::getAll() as $pId => $player){
        self::$method($pId);
      }
    }
  }



  /*
   * Discard previous cards
   */
  public function discard()
  {
    self::callWithRightArguments("discardAux");
  }

  protected function discardAux($playerId = null, $discardAll = false)
  {
    foreach (self::getStacks($playerId) as $stackId => $stack) {
      if(Globals::isStandard()){
        // Standard mode : Discard last flipped card if any, flip the current construction card if any, draw a new card
        self::moveAllInLocation($stack, 'discard', $discardAll? 2 : 1);
        if(!$discardAll)
          self::moveAllInLocation($stack, $stack,    0, 1);
      } else {
        // Discard all previously drawn cards
        self::moveAllInLocation($stack, 'discard', 0);
      }
    }
  }



  /*
   * Draw a new set of cards for new turn. Will be called either :
   *   - only once with $playerId = null if in standard or solo mode
   *   - once per player's id in expert mode
   */
  public function draw()
  {
     return self::callWithRightArguments("drawAux");
  }

  protected function drawAux($playerId = null)
  {
    $drawnCards = [];
    foreach (self::getStacks($playerId) as $stackId => $stack) {
      ///// Drawing new card /////
      // In expert mode, the first card was drafter by another player in prev turn
      $fromLocation = ($stackId == 0 && Globals::isExpert())? "for_{$playerId}" : "deck";
      $drawnCard = self::pickOneForLocation($fromLocation, $stack);

      // Drawing the solo card ? Re-draw another card immediately
      if ($drawnCard['action'] == SOLO) {
        self::move($drawnCard['id'], 'removed');
        $drawnCard = self::pickOneForLocation($fromLocation, $stack);
        self::soloCardDrawn();
      }

      $drawnCard['stackId'] = $stackId;
      $drawnCards[$stackId] = $drawnCard;
    }

    Notifications::newCards($playerId, $drawnCards);
  }


  /*
   * Triggered when the solo card is drawn
   */
  public function soloCardDrawn(){
    Notifications::soloCard();

    // Validate all plans with a mock id of -1
    foreach(PlanCards::getCurrent() as $plan){
      $query = new Helpers\QueryBuilder('plan_validation');
      $query->insert([
        'card_id' => $plan->getId(),
        'player_id' => -1,
        'turn' => Globals::getCurrentTurn() - 1,
      ]);
    }
    Notifications::updatePlayersData();
  }



  /*
   * In expert mode, put the non-used card on a custom location for next player
   */
  public function prepareCardsForNextTurn($pId, $stacks, $nextPId){
    foreach (self::getStacks($pId) as $stackId => $stack) {
      if(in_array($stackId, $stacks))
        continue;

      self::moveAllInLocation($stack, "for_$nextPId");
      Notifications::giveThirdCardToNextPlayer($pId, $stackId, $nextPId);
    }
  }

  /*
   * Allow to reshuffle cards once someone finish a plan
   */
  public function reshuffle(){
    self::callWithRightArguments("reshuffleAux");
    Notifications::reshuffle();
    self::reformDeckFromDiscard("deck");


    // Change the turn temporarily to have correct zindex
    $n = Globals::getCurrentTurn();
    welcometo::get()->setGamestateValue("currentTurn", $n - 1);

    // Draw fresh new cards
    if(Globals::isStandard()){
      self::draw();
      self::discard();
    }
    else if(Globals::isExpert()){
      foreach(Players::getAll() as $pId => $player){
        self::pickForLocation(1, 'deck', "for_{$pId}");
      }
    }

    // Reset turn to correct value
    welcometo::get()->setGamestateValue("currentTurn", $n);
  }

  protected function reshuffleAux($playerId = null){
    self::discardAux($playerId, true);
  }

  ////////////////////////////////////
  ////////////////////////////////////
  ////////////  GETTERS  /////////////
  ////////////////////////////////////
  ////////////////////////////////////

  /*
   * Get the content of the three stacks
   */
  public function getForPlayer($pId)
  {
    $cards = [];
    foreach (self::getStacks($pId) as $stackId => $stack) {
      $cards[$stackId] = self::getTopOf($stack, 2, false)->toArray();
    }

    return $cards;
  }

  /*
   * Get all the possible combinations
   */
  public function getPossibleCombinations($pId)
  {
    $stacks = self::getForPlayer($pId);
    $result = [];
    if(Globals::isStandard()){
      for($i = 0; $i < 3; $i++){
        array_push($result, [
          'stacks' => $i,
          'action' => $stacks[$i][0]['action'],
          'number' => $stacks[$i][1]['number'],
        ]);
      }
    } else {
      for($i = 0; $i < 3; $i++){
        for($j = 0; $j < 3; $j++){
          if($i == $j) continue;

          array_push($result, [
            'stacks' => [$i, $j],
            'number' => $stacks[$i][0]['number'],
            'action' => $stacks[$j][0]['action'],
          ]);
        }
      }
    }

    return $result;
  }



  /*
   * Get the combination corresponding to the stack(s) selection
   */
  public function getCombination($pId, $stack)
  {
    $stacks = self::getForPlayer($pId);
    $data = [];
    if(Globals::isStandard()){
      $data['action'] = $stacks[$stack][0]['action'];
      $data['number'] = $stacks[$stack][1]['number'];
    } else {
      $data['number'] = $stacks[$stack[0]][0]['number'];
      $data['action'] = $stacks[$stack[1]][0]['action'];
    }

    return $data;
  }
}
