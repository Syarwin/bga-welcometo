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
  protected static $autoIncrement = false;
  protected static function cast($card){
    if(!isset(self::$plans[$card['id']]))
      throw new \BgaVisibleSystemException("Trying to fetch a plan with no corresponding id : $planId");

    $plan = self::$plans[$card['id']];
    $className = "\WTO\Plans\\" . $plan[3] . "Plan";
    return new $className($plan, $card);
  }

  protected static $plans = [
    /*
     * List of plans with :
     *   - expansion/variant
     *   - stack number
     *   - scores
     *   - class name to handle canBeScore / args / score functions
     *   - additional arg passed to the constructor
     */
    [BASIC, 1, [8, 4], 'Estate', [1,1,1,1,1,1] ],
    [BASIC, 1, [8, 4], 'Estate', [2,2,2,2] ],
    [BASIC, 1, [8, 4], 'Estate', [3,3,3] ],
    [BASIC, 1, [6, 3], 'Estate', [4,4] ],
    [BASIC, 1, [8, 4], 'Estate', [5,5] ],
    [BASIC, 1, [10, 6], 'Estate',[6,6] ],

    [BASIC, 2, [11, 6], 'Estate', [1,1,1,6] ],
    [BASIC, 2, [10, 6], 'Estate', [5,2,2] ],
    [BASIC, 2, [12, 7], 'Estate', [3,3,4] ],
    [BASIC, 2, [8, 4], 'Estate', [3,6] ],
    [BASIC, 2, [9, 5], 'Estate', [4,5] ],
    [BASIC, 2, [9, 5], 'Estate', [4,1,1,1] ],

    [BASIC, 3, [12, 7], 'Estate', [1,2,6] ],
    [BASIC, 3, [13, 7], 'Estate', [1,4,5] ],
    [BASIC, 3, [7, 3], 'Estate', [3,4] ],
    [BASIC, 3, [7, 3], 'Estate', [2,5] ],
    [BASIC, 3, [11, 6], 'Estate', [1,2,2,3] ],
    [BASIC, 3, [13, 7], 'Estate', [2,3,5] ],


    [ADVANCED, 1, [8, 4], 'FullStreet', 2],
    [ADVANCED, 1, [6, 3], 'FullStreet', 0],
    [ADVANCED, 1, [8, 3], 'FiveBis', null],
    [ADVANCED, 1, [6, 3], 'SevenTemp', null],
    [ADVANCED, 1, [7, 4], 'Extremities', null],

    [ADVANCED, 2, [7, 4], 'Decorative', ['park'] ],
    [ADVANCED, 2, [10,5], 'CompleteStreet', null],
    [ADVANCED, 2, [7, 4], 'Decorative', ['pool'] ],
    [ADVANCED, 2, [10, 5], 'Decorative', ['pool&park', 2] ],
    [ADVANCED, 2, [8, 3], 'Decorative', ['pool&park', 1] ],
  ];


  protected static function getAllInstances()
  {
    $res = [];
    foreach(self::$plans as $id => $plan){
      $className = "\WTO\Plans\\" . $plan[3] . "Plan";
      $res[$id] = new $className($plan);
    }

    return $res;
  }


  public function getCurrent()
  {
    return self::getInLocationQ(['stack', '%'])->orderBy('location')->get(false);
  }

  public function setupNewGame($players)
  {
    // Create the available cards, depending on variant/expansions
    $cards = [];
    foreach(self::getAllInstances() as $id => $plan){
      if($plan->isAvailable()){
        array_push($cards, [
          'id' => $id,
          'location' => 'deck_' . $plan->getStack(),
        ]);
      }
    }
    self::create($cards);


    // Pick the three projects
    for($i = 1; $i <= 3; $i++){
      self::shuffle(['deck', $i]);
      self::pickForLocation(1, ['deck', $i], ['stack', $i]);
    }
  }


  public function getUiData()
  {
    return self::getCurrent()->map(function($plan){
      return $plan->getUiData();
    });
  }

  public function getCurrentValidations()
  {
    return self::getCurrent()->map(function($plan){
      return $plan->getValidations();
    });
  }


  public function getCurrentScores()
  {
    return self::getCurrent()->map(function($plan){
      return $plan->getScores();
    });
  }

  public function getScore($player)
  {
    $res = [];
    foreach(self::getCurrentScores() as $stack => $scores){
      $res['plan-' . $stack] = $scores[$player->getId()] ?? 0;
    }
    $res['plan-total'] = $res['plan-0'] + $res['plan-1'] + $res['plan-2'];
    return $res;
  }

  public function areAllPlansScored($player)
  {
    $scores = self::getScore($player);
    return $scores['plan-0'] > 0 && $scores['plan-1'] > 0 && $scores['plan-2'] > 0;
  }

  public function clearTurn($pId)
  {
    $query = new Helpers\QueryBuilder('plan_validation');
    $query->delete()->where('player_id', $pId)->where('turn', Globals::getCurrentTurn() )->run();
  }

}
