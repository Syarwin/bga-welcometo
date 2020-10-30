<?php
namespace WTO;
use welcometo;
/*
 * Plan Cards
 */
class PlanCards extends Helpers\Pieces
{
  protected static $table = "plan_cards";
	protected static $prefix = "card_";
  protected static $customFields = ['approved'];
  protected static function cast($card){
    return $card;
  }

  protected static $scores = [
    [8, 4], [8, 4], [8, 4], [6, 3], [8, 4], [10, 6],
    [11, 6], [10, 6], [12, 7], [8, 4], [9, 5], [9, 5],
    [12, 7], [13, 7], [7, 3], [7, 3], [11, 6], [13, 7],

    [8, 4], [6, 3],  [8, 3], [6, 3], [7, 4],
    [7, 4], [10, 5], [7, 4], [10, 5],[8, 3],
  ];


  public function setupNewGame($players){
    // Basic cards
    self::create([
      ['location' => 'deck_1', 'nbr' => 6],
      ['location' => 'deck_2', 'nbr' => 6],
      ['location' => 'deck_3', 'nbr' => 6],
    ]);

    // Advanced variant
    if(welcometo::isAdvancedGame()){
      self::create([
        ['location' => 'deck_1', 'nbr' => 6],
        ['location' => 'deck_2', 'nbr' => 6],
      ]);
    }

    // Pick the three projects
    for($i = 1; $i <= 3; $i++){
      self::shuffle(['deck', $i]);
      self::pickForLocation(1, ['deck', $i], ['stack', $i]);
    }
  }
}
