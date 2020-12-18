<?php
namespace WTO\Plans;
use \WTO\Scribbles;
use \WTO\Game\UserException;
use \WTO\Game\Notifications;
use \WTO\Helpers\Utils;
use \WTO\Actions\TopFence;

function subtract_array($array1,$array2){
  foreach ($array2 as $item) {
    $key = array_search($item, $array1);
    if($key !== false)
      unset($array1[$key]);
  }
  return array_values($array1);
}


class EstatePlan extends AbstractPlan
{
  public function __construct($info, $card = null){
    parent::__construct($info, $card);

    $this->desc = [
      clienttranslate("To fullfill this City Plan, the player must complete all the required housing estates."),
      clienttranslate("The estates do not have to be in the same street. There is no restriction to the location of a Plan's estates.").'<br />',
      clienttranslate("Estate sizes required: ").implode(", ", $this->conditions),
    ];
  }


  protected function getAvailableEstates($player)
  {
    $estates = $player->getEstates();

    // Remove estates already used
    $topFences = TopFence::getOfPlayerStructured($player);
    Utils::filter($estates, function($estate) use ($topFences){
      for($i = 0; $i < $estate['size']; $i++){
        if(!is_null($topFences[$estate['x']][$estate['y'] + $i]))
          return false;
      }
      return true;
    });

    return $estates;
  }


  public function canBeScored($player)
  {
    if(!parent::canBeScored($player))
      return false;

    $estates = $this->getAvailableEstates($player);
    // Compute the size and make the (multiple value) difference
    $sizes = array_map(function($estate){ return $estate['size']; }, $estates);
    $diff = subtract_array($this->conditions, $sizes);
    return empty($diff);
  }


  public function argValidate($player)
  {
    return [
      'conditions' => $this->conditions,
      'estates' => $this->getAvailableEstates($player),
    ];
  }

  protected function checkValidate($player, $args)
  {
    // Check estates belongs to player
    $estates = $player->getEstates(false);
    foreach($args as $estate){
      if(!in_array($estate, $estates)){
        throw new UserException(totranslate("This is not an estate"));
      }
    }

    // Checks conditions are met
    $sizes = array_map(function($estate){ return $estate['size']; }, $args);
    $diff = subtract_array($this->conditions, $sizes);
    if(!empty($diff))
      throw new UserException(totranslate("Conditions are not fullfiled"));
  }



  public function validate($player, $args){
    // Check estates
    $this->checkValidate($player, $args);
    $scribbles = [];
    foreach($args as $estate){
      for($y = $estate['y']; $y < $estate['y'] + $estate['size']; $y++){
        $zone = [$estate['x'], $y];
        array_push($scribbles, Scribbles::add($player->getId(), 'top-fence', $zone) );
      }
    }
    Notifications::addMultipleScribbles($player, $scribbles);
    parent::validate($player, $args);
  }
}
