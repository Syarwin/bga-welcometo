<?php
namespace WTO\States\Expansions;

use WTO\Actions\IceTruck;
use WTO\Actions\IceCream;

use WTO\Game\Globals;
use WTO\Game\Players;
use WTO\Game\Log;
use WTO\Game\StateMachine;
use WTO\Game\UserException;
use WTO\Game\Notifications;
use WTO\Scribbles;

/*
 * Handle the IceCream expansion
 */
trait IceCreamTrait
{
  /**
   * stIceCream: check if ice cream can be distributed already or if we need a choice to be made for the third row
   */
  public function stIceCream($player)
  {
    $house = $player->getLastHouse();
    $truckPositions = IceTruck::getTruckPositions($player);
    if(!is_null($truckPositions[$house['x']])){
      self::distributeIceCream($player);
      return true;
    }
  }


  // Nothing to send, the player just need to choose left or right for the truck on the last row
  public function argIceCream()
  {

  }

  /**
   * chooseIceTruck: player chose the ice truck he wants to move
   */
  public function chooseIceTruck($choice)
  {
    $player = Players::getCurrent();
    $player->scribbleZone([3, 1 - $choice]);
    self::distributeIceCream($player);
  }


  /**
   * distributeIceCream: move the ice truck and check ice creams
   */
  public function distributeIceCream($choice)
  {
    $player = Players::getCurrent();
    $scribbles = [];

    // Add the scribbles for iceCreams
    $cones = IceCream::getConesToScribble($player);
    foreach($cones as $cone){
      array_push($scribbles, Scribbles::add($player->getId(), 'ice-cream', $cone) );
    }

    // Add the scribbles for iceTruck
    $houses = IceTruck::getHousesToCross($player);
    foreach($houses as $house){
      array_push($scribbles, Scribbles::add($player->getId(), 'ice-truck', $house) );
    }

    // Did we reach the end of the line
    $endOfStreet = null;
    $sizes = [10, 11, 12];
    foreach($houses as $house){
      if($house['y'] == 0 || $house['y'] == $sizes[$house['x']]){
        $endOfStreet = $house;
      }
    }

    if(!is_null($endOfStreet)){
      $zone = IceCream::reachEndOfStreet($player, $endOfStreet);
      if(!is_null($zone)){
        array_push($scribbles, Scribbles::add($player->getId(), 'ice-cream', $zone));
      }
    }

    // Notify all the new scribbles
    Notifications::addMultipleScribbles($player, $scribbles);

    // Move on to next state depending on the action card
    $combination = $player->getCombination();
    StateMachine::nextState($combination['action']);
  }
}
