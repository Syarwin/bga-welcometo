<?php
namespace WTO;
use welcometo;

/*
 * Construction Cards
 */
class ConstructionCards extends Helpers\Pieces
{
  protected static $table = "construction_cards";
	protected static $prefix = "card_";
  protected static $customFields = ['number', 'action'];
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
    11 => [   2,    0,    0,    1,    1,    2],
    12 => [   1,    0,    1,    1,    1,    1],
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
      self::draw(null, true);
    } else {
      if(Globals::isSolo()){
        // Solo mode setup : add the solo card on the bottom half
        self::soloSetupNewGame();
      }
      else if(Globals::isExpert()){
        // Expert mode : pre-draft one card per player
        foreach($players as $playerId){
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
   * Draw a new set of cards for new turn. Will be called either :
   *   - only once with $playerId = null if in standard or solo mode
   *   - once per player's id in expert mode
   */
  public function draw($playerId = null)
  {
    $drawnCards = [];
    $soloCardDrawn = false;
    foreach (self::getStacks($playerId) as $stackId => $stack) {
      ///// Cleaning stack /////
      if(Globals::isStandard()){
        // Standard mode : Discard last flipped card if any, flip the current construction card if any, draw a new card
        self::moveAllInLocation($stack, 'discard', 1);
        self::moveAllInLocation($stack, $stack,    0, 1);
      } else {
        // Discard all previously drawn cards
        self::moveAllInLocation($stack, 'discard', 0); // TODO : remove 0 ?
      }

      ///// Drawing new card /////
      // In expert mode, the first card was drafter by another player in prev turn
      $fromLocation = ($stackId == 0 && Globals::isExpert())? "for_{$this->playerId}" : "deck";
      $drawnCard = self::pickOneForLocation($fromLocation, $stack);

      // Drawing the solo card ? Re-draw another card immediately
      if ($drawnCard['action'] == SOLO) {
        self::move($drawCard['id'], 'removed');
        $drawnCard = self::pickOneForLocation($fromLocation, $stack);
        $soloCardDrawn = true;
      }

      $drawnCards[$stackId] = $drawnCard;
    }

    return [
      'drawnCards' => $drawnCards,
      'soloCardDrawn' => $soloCardDrawn
    ];
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
      $cards[$stackId] = self::getTopOf($stack, 2)->toArray();
    }

    return $cards;
  }


  /*
   * Get the combination corresponding to the stack(s) selection
   */
  public function getCombination($pId, $stack)
  {
    $stacks = self::getForPlayer($pId);
    $data = [];
    if(Globals::isStandard()){
      $data['number'] = $stacks[$stack][0]['number'];
      $data['action'] = $stacks[$stack][1]['action'];
    } else {
      $data['number'] = $stacks[$stack[0]][0]['number'];
      $data['action'] = $stacks[$stack[1]][0]['action'];
    }

    return $data;
  }
}
