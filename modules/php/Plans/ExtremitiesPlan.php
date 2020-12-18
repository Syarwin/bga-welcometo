<?php
namespace WTO\Plans;
use \WTO\Game\Notifications;
use \WTO\Scribbles;
use \WTO\Actions\TopFence;

class ExtremitiesPlan extends AbstractPlan
{
  protected $automatic = true;
  public function __construct($info, $card = null){
    parent::__construct($info, $card);

    $this->desc = [
      clienttranslate("To fulfill this City Plan, the first and last house of each street must be built."),
      clienttranslate("Warning: you cannot re-use a house already used for another city plan!"),
    ];
  }

  protected $pos = [
    [0,0], [0,9],
    [1,0], [1,10],
    [2,0], [2,11],
  ];

  public function canBeScored($player)
  {
    if(!parent::canBeScored($player))
      return false;

    $topFences = TopFence::getOfPlayerStructured($player);
    $streets = $player->getStreets();
    $built = true;
    for($i = 0; $i < count($this->pos) && $built; $i++){
      $x = $this->pos[$i][0];
      $y = $this->pos[$i][1];
      $built = !is_null($streets[$x][$y]) && is_null($topFences[$x][$y]);
    }

    return $built;
  }


  public function validate($player, $args){
    $scribbles = [];
    for($i = 0; $i < count($this->pos); $i++){
      $x = $this->pos[$i][0];
      $y = $this->pos[$i][1];
      $zone = [$x, $y];
      array_push($scribbles, Scribbles::add($player->getId(), 'top-fence', $zone) );
    }

    Notifications::addMultipleScribbles($player, $scribbles);
    parent::validate($player, $args);
  }
}
