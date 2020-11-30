<?php
namespace WTO\Game;
use welcometo;
use WTO\Actions\RealEstate;
use WTO\PlanCards;

class Stats
{
  protected static function init($type, $name, $value = 0){
    welcometo::get()->initStat($type, $name, $value);
  }

  public static function inc($name, $player = null, $value = 1, $log = true){
    $pId = is_null($player)? null : ( ($player instanceof \WTO\Player)? $player->getId() : $player );
    Log::insert($pId, 'changeStat', [ 'name' => $name, 'value' => $value ]);
    welcometo::get()->incStat($value, $name, $pId);
  }


  protected static function get($name, $player = null){
    welcometo::get()->getStat($name, $player);
  }

  protected static function set($value, $name, $player = null){
    $pId = is_null($player)? null : ( ($player instanceof \WTO\Player)? $player->getId() : $player );
    welcometo::get()->setStat($value, $name, $pId);
  }


  public static function setupNewGame(){
    $stats = welcometo::get()->getStatTypes();

    self::init('table', 'turns_number');
    self::init('table', 'permit_refusal_ending', false);
    self::init('table', 'projects_ending', false);
    self::init('table', 'all_houses_ending', false);

    foreach ($stats['player'] as $key => $value) {
      if($value['id'] > 10 && $value['type'] == 'int' && $key != 'empty_slots_number')
        self::init('player', $key);
    }
    self::init('player', "empty_slots_number", 33);
  }


  public static function newTurn(){
    self::set(Globals::getCurrentTurn(), 'turns_number');
  }

  public static function endOfGame($type){
    self::set(true, $type);
  }

  public static function chooseCards($player){
    $combination = $player->getCombination();
    $statNames = [
      SURVEYOR => "selected_surveyor_number",
      ESTATE   => "selected_real_estate_number",
      PARK     => "selected_landscaper_number",
      POOL     => "selected_pool_manufacturer_number",
      TEMP     => "selected_temp_agency_number",
      BIS      => "selected_bis_number"
    ];
    self::inc($statNames[$combination['action']], $player);
  }

  public static function writeNumber($player, $house){
    $name = $house['number'] == ROUNDABOUT? 'roundabout_built_number' : 'houses_built_number';
    self::inc($name, $player);
    self::inc("empty_slots_number", $player, -1);
  }

  public static function addScribble($player, $scribble, $silent){
    if($silent) return;

    $statNames = [
      "permit-refusal" => 'permit_refusal_number',
      "estate-fence" => 'used_surveyor_number',
      "score-estate" => 'used_real_estate_number',
      "score-temp" => 'used_temp_agency_number',
      "score-bis" => 'used_bis_number',
      "score-pool" => 'used_pool_manufacturer_number',
      "park" => 'used_landscaper_number',
    ];
    self::inc($statNames[$scribble['type']], $player);
  }


  public static function updatePlayersData(){
    $planValidations = PlanCards::getCurrentValidations();

    foreach(Players::getAll() as $pId => $player){
      $scoreSheet = $player->getScoreSheet();

      // Scores
      $assoc = [
        "scoring_plan" => 'plan-total',
        "scoring_park" => 'park-total',
        "scoring_pool" => 'pool-total',
        "scoring_temp" => 'temp-total',
        "scoring_estate_1" => 'estate-total-0',
        "scoring_estate_2" => 'estate-total-1',
        "scoring_estate_3" => 'estate-total-2',
        "scoring_estate_4" => 'estate-total-3',
        "scoring_estate_5" => 'estate-total-4',
        "scoring_estate_6" => 'estate-total-5',
        "scoring_estate_total" => 'estate-total',
        "scoring_bis" => 'bis-total',
        "scoring_roundabout" => 'roundabout-total',
        "scoring_permit_refusal" => 'permit-total',
      ];
      $negatives = ["scoring_bis", "scoring_roundabout", "scoring_permit_refusal"];

      foreach($assoc as $statName => $scoreName){
        $score = $scoreSheet['scores'][$scoreName];
        if(in_array($statName, $negatives))
          $score = -$score;

        self::set($score, $statName, $pId);
      }

      // Estates
      $estates = RealEstate::getAssocSizeNumber($player);
      for($i = 0; $i < 6; $i++){
        self::set($estates[$i], "size_". ($i + 1)."_estates", $player);
      }

      // Plan validations
      $plans = [0, 0];
      for($i = 0; $i < 3; $i++){
        if(isset($planValidations[$i][$pId])){
          $plans[$planValidations[$i][$pId]['rank']]++;
        }
      }

      self::set($plans[0], "projects_number_first", $player);
      self::set($plans[1], "projects_number_second", $player);
    }
  }
}

?>
