<?php
namespace WTO\Plans;
use \WTO\Game\Globals;

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

  public function getStack(){ return $this->stack; }
  public function isAvailable(){
    return $this->variant == BASIC || Globals::isAdvanced();
  }

  abstract public function canBeScored($player);
  abstract public function argValidatePlans($player);
  abstract public function validatePlans($player, $args);
}
