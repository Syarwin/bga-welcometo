<?php
namespace WTO\Plans;
use \WTO\Game\Globals;
use \WTO\Game\Notifications;
use \WTO\Helpers\QueryBuilder;

abstract class AbstractPlan
{
  protected $id = null;

  protected $variant;
  protected $stack;
  protected $scores;
  protected $conditions;

  public function __construct($info, $card = null){
    $this->variant = $info[0];
    $this->stack = $info[1];
    $this->scores = $info[2];
    $this->conditions = $info[4];

    if(is_null($card))
      return;
    $this->id = $card['id'];
  }

  public function getUiData(){
    return [
      'id' => $this->id,
    ];
  }

  public function getId(){ return $this->id; }
  public function getStack(){ return $this->stack; }
  public function isAvailable(){
    return $this->variant == BASIC || Globals::isAdvanced();
  }

  public function canBeScored($player)
  {
    $scores = $this->getScores();
    return !\array_key_exists($player->getId(), $scores);
  }


  abstract public function argValidate($player);
  public function validate($player, $args){
    $query = new QueryBuilder('plan_validation');
    $query->insert([
      'card_id' => $this->id,
      'player_id' => $player->getId(),
      'turn' => Globals::getCurrentTurn(),
    ]);
    Notifications::planScored($player, $this->id, $this->getValidations());
  }

  public function getValidations()
  {
    $query = new QueryBuilder('plan_validation');
    $validations = $query->where('card_id', $this->id)->get(false);
    $turns = [];
    foreach($validations as $validation){
      $turns[$validation['player_id']] = $validation['turn'];
    }

    asort($turns);
    $firstValue = null;
    $validations = [];
    foreach($turns as $pId => $turn){
      if(is_null($firstValue))
        $firstValue = $turn;
      $validations[$pId] = ["rank" => $turn == $firstValue? 0 : 1, "turn" => $turn];
    }

    return $validations;
  }

  public function getScores()
  {
    $validations = self::getValidations();
    $scores = [];
    foreach($validations as $pId => $val){
      $scores[$pId] = $this->scores[$val["rank"]];
    }

    return $scores;
  }
}
