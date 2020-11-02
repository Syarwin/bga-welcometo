<?php
namespace WTO;
use welcometo;

class Player extends Helpers\DB_Manager
{
  public function __construct($row)
  {
    $this->id = (int) $row['player_id'];
    $this->no = (int) $row['player_no'];
    $this->name = $row['player_name'];
    $this->color = $row['player_color'];
    $this->eliminated = $row['player_eliminated'] == 1;
    $this->zombie = $row['player_zombie'] == 1;
    $this->state = $row['player_state'];
  }

  private $id;
  private $no; // natural order
  private $name;
  private $color;
  private $eliminated = false;
  private $zombie = false;


  public function getId(){ return $this->id; }
  public function getNo(){ return $this->no; }
  public function getName(){ return $this->name; }
  public function getColor(){ return $this->color; }
  public function isEliminated(){ return $this->eliminated; }
  public function isZombie(){ return $this->zombie; }
  public function getState(){ return $this->state; }

  public function getUiData()
  {
    return [
      'id'        => $this->id,
      'no'        => $this->no,
      'name'      => $this->name,
      'color'     => $this->color,
    ];
  }


  public function getConstructionCards()
  {
    return ConstructionCards::getForPlayer($this->id);
  }

  public function hasSomethingToCancel()
  {
    return !empty(Log::getLastActions($this->id));
  }

  public function getSelectedCards()
  {
    $selectCardAction = Log::getLastAction('selectCard', $this->id);
    return is_null($selectCardAction)? null : $selectCardAction['arg'];
  }

}
