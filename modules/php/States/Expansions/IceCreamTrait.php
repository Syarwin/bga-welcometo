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
use WTO\Helpers\Utils;
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
    if (!is_null($truckPositions[$house['x']])) {
      self::distributeIceCream();
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
    self::distributeIceCream();
  }

  /**
   * distributeIceCream: move the ice truck and check ice creams
   */
  public function distributeIceCream($nextState = null)
  {
    $player = Players::getCurrent();
    $scribbles = [];

    // Add the scribbles for iceCreams
    $cones = IceCream::getConesToScribble($player);
    foreach ($cones as $cone) {
      array_push($scribbles, Scribbles::add($player->getId(), 'ice-cream', $cone));
    }

    // Add the scribbles for iceTruck
    $houses = IceTruck::getHousesToCross($player);
    foreach ($houses as $house) {
      array_push($scribbles, Scribbles::add($player->getId(), 'ice-truck', $house));
    }

    // Did we reach the end of the line
    $endOfStreet = IceTruck::getEndOfStreet($player, $houses);

    if (!is_null($endOfStreet)) {
      $zone = IceCream::reachEndOfStreet($player, $endOfStreet);
      if (!is_null($zone)) {
        array_push($scribbles, Scribbles::add($player->getId(), 'ice-cream', $zone));
      }
    }

    // Notify all the new scribbles
    Notifications::addMultipleScribbles($player, $scribbles);
    $player->updateScores();

    // Move on to next state depending on the action card
    $combination = $player->getCombination();
    StateMachine::nextState($nextState ?? $combination['action']);
  }

  /**
   * checkIceCreamBonuses: remove bonuses for other if someone reached a new bonus
   */
  public function checkIceCreamBonuses()
  {
    $turn = Globals::getCurrentTurn() - 1;

    // Get all ice-cream scribbles of last turn
    $scribbles = Scribbles::getInLocationQ('%_ice-cream_%_%')
      ->where('turn', $turn)
      ->get()
      ->toArray();
    // Keep the ones corresponding to a end-of-street bonus (y < 0)
    Utils::filter($scribbles, function ($scribble) {
      return $scribble['y'] < 0;
    });

    if (!empty($scribbles)) {
      // Get all the lines that needs to be crossed off for other players
      $streets = array_unique(
        array_map(function ($scribble) {
          return $scribble['x'];
        }, $scribbles)
      );

      foreach ($streets as $x) {
        $scribbles = [];

        // Check for all other players if they have the bonus already or not
        foreach (Players::getAll() as $player) {
          $zones = IceCream::getBonusesToCross($player, $x);
          foreach ($zones as $y) {
            $scribbles[] = Scribbles::add(
              $player->getId(),
              'ice-cream',
              [
                'x' => $x,
                'y' => $y,
                'state' => 0,
              ],
              $turn
            );
          }
        }

        Notifications::crossOffIceCreamBonuses($scribbles, $x);
      }
    }
  }
}
