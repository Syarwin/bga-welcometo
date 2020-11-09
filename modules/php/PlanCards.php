<?php
namespace WTO;
use WTO\Game\Globals;
/*
 * Plan Cards
 */
class PlanCards extends Helpers\Pieces
{
  protected static $table = "plan_cards";
	protected static $prefix = "card_";
  protected static $customFields = ['approved'];
  protected static function cast($card){
    return [
      'id' => $card['id'],
      'approved' => $card['approved'] == 1,
    ];
  }

  protected static $scores = [
    [8, 4], [8, 4], [8, 4], [6, 3], [8, 4], [10, 6],
    [11, 6], [10, 6], [12, 7], [8, 4], [9, 5], [9, 5],
    [12, 7], [13, 7], [7, 3], [7, 3], [11, 6], [13, 7],

    [8, 4], [6, 3],  [8, 3], [6, 3], [7, 4],
    [7, 4], [10, 5], [7, 4], [10, 5],[8, 3],
  ];

  protected static $conditions = [
    [1,1,1,1,1,1],    [2,2,2,2],    [3,3,3],    [4,4],    [5,5],    [6,6],
    [1,1,1,6],    [5,2,2],    [3,3,4],    [3,6],    [4,5],    [4,1,1,1],
    [1,2,6],    [1,4,5],    [3,4],    [2,5],    [1,2,2,3],    [2,3,5],

    FULL_STREET_3, FULL_STREET_1, FIVE_BIS, SEVEN_TEMP, ALL_ENDS_BUILT,
    FULL_2PARK, COMPLETE_STREET, FULL_2POOL, FULL_PARK_POOL_3, FULL_PARK_POOL_2
  ];


  public function setupNewGame($players)
  {
    // Basic cards
    self::create([
      ['location' => 'deck_1', 'nbr' => 6],
      ['location' => 'deck_2', 'nbr' => 6],
      ['location' => 'deck_3', 'nbr' => 6],
    ]);

    // Advanced variant
    if(Globals::isAdvanced()){
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


  public function getUiData()
  {
    return self::getInLocation(['stack', '%'])->toArray();
  }
}
