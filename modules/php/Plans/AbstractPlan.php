<?php
namespace WTO\Plans;
use \WTO\Game\Globals;
use \WTO\Helpers\QueryBuilder;

abstract class AbstractPlan
{
  protected $id = null;
  protected $approved = null;

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
    $this->approved = $card['approved'] == 1;
  }

  public function getUiData(){
    return [
      'id' => $this->id,
      'approved' => $this->approved,
    ];
  }

  public function getId(){ return $this->id; }
  public function getStack(){ return $this->stack; }
  public function isAvailable(){
    return $this->variant == BASIC || Globals::isAdvanced();
  }

  abstract public function canBeScored($player);
  abstract public function argValidate($player);
  public function validate($player, $args){
    $query = new QueryBuilder('plan_validation');
    $query->insert([
      'card_id' => $this->id,
      'player_id' => $player->getId(),
      'turn' => Globals::getCurrentTurn(),
    ]);
  }

  public function getScores()
  {
    $query = new QueryBuilder('plan_validation');
    $validations = $query->where('card_id', $this->id)->get(false);
    $turns = [];
    foreach($validations as $validation){
      $turns[$validation['player_id']] = $validation['turn'];
    }

    asort($turns);
    $firstValue = null;
    $scores = [];
    foreach($turns as $pId => $turn){
      if(is_null($firstValue))
        $firstValue = $turn;
      $scores[$pId] = $turn == $firstValue? $this->scores[0] : $this->scores[1];
    }

    return $scores;
  }
}
