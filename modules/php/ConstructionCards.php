<?php
namespace WTO;

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

    if(count($players) == 1){
      // TODO
      // Draw two decks
      // Add the solo card on the bottom one
      // Shuffle the bottom one
      // Merge the two
    }
  }
}
