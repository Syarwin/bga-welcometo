<?php
namespace WTO\States;

use \WTO\Actions\RealEstate;
use \WTO\Actions\Park;
use \WTO\Actions\Temp;
use \WTO\Actions\Bis;
use \WTO\Actions\Pool;
use \WTO\Actions\Surveyor;

use \WTO\Game\Players;
use \WTO\Game\Globals;
use \WTO\Game\Log;
use \WTO\Game\StateMachine;
use \WTO\Game\UserException;

/*
 * Handle everything related to actions
 */
trait ActionsTrait
{
  function passAction()
  {
    StateMachine::checkAction("pass");
    // TODO : Log the passing ?
    StateMachine::nextState("pass");
  }

  /*
   * Generic scribble zone action
   */
  function scribbleZone($zone)
  {
    StateMachine::checkAction("scribbleZone");
    $player = Players::getCurrent();
    $args = StateMachine::getArgsOfPlayer($player);
    if(!in_array($zone, $args['zones']))
      throw new UserException(totranslate("You cannot scribble this zone"));

    $player->scribbleZone($zone);
    $player->updateScores();
    StateMachine::nextState("scribbleZone");
  }

  ///////////////////////////
  ///////// FENCES //////////
  ///////////////////////////
  function argActionSurveyor($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data['zones'] = Surveyor::getAvailableZones($player);
    return $data;
  }


  ///////////////////////////
  ///////// ESTATE //////////
  ///////////////////////////
  function argActionEstate($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data['zones'] = RealEstate::getAvailableZones($player);
    return $data;
  }


  ///////////////////////////
  ///////// PARKS ///////////
  ///////////////////////////
  function argActionPark($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data['zones'] = Park::getAvailableZones($player);
    return $data;
  }


  ///////////////////////////
  ///////// POOL ///////////
  ///////////////////////////
  function stActionPool($player)
  {
    if(!Pool::canBuild($player)){
      StateMachine::nextState("pass");
      return true;
    }
  }

  function argActionPool($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data['zones'] = Pool::getAvailableZones($player);
    $data['lastHouse'] = $player->getLastHouse();
    return $data;
  }


  //////////////////////////
  ///////// TEMP ///////////
  //////////////////////////
  function stActionTemp($player)
  {
    // Write the next available spot in the score sheet
    $zones = Temp::getAvailableZones($player);
    if(!empty($zones))
      $player->scribbleZone($zones[0]);
    StateMachine::nextState("scribbleZone");
    return true; // Skip this state
  }

  ///////////////////////////////
  ///////// ACTION BIS //////////
  ///////////////////////////////
  function argActionBis($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data["numbers"] = $player->getAvailableNumbersForBis();
    return $data;
  }

  function writeNumberBis($number, $pos)
  {
    // Sanity checks
    StateMachine::checkAction("writeNumberBis");
    $player = Players::getCurrent();
    $args = self::argActionBis($player);
    if(!isset($args['numbers'][$number]) || !in_array($pos, $args['numbers'][$number]))
      throw new UserException(totranslate("You cannot write this number bis in this house"));

    // Write the number
    $player->writeNumber($number, $pos, true);
    // Write the next available spot in the score sheet
    $zones = Bis::getAvailableZones($player);
    $player->scribbleZone($zones[0]);

    // Move on to next state
    StateMachine::nextState("bis");
  }
}
