<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * welcometo implementation : © Geoffrey VOYER <geoffrey.voyer@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * welcometo.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

// $autoloadFuncs = spl_autoload_functions();
//var_dump($autoloadFuncs);
/*
foreach($autoloadFuncs as $unregisterFunc)
{
    spl_autoload_unregister($unregisterFunc);
}
*/

$swdNamespaceAutoload = function ($class) {
  $classParts = explode('\\', $class);
  if ($classParts[0] == 'WTO') {
    array_shift($classParts);
    $file = dirname(__FILE__) . '/modules/php/' . implode(DIRECTORY_SEPARATOR, $classParts) . '.php';
    if (file_exists($file)) {
      require_once $file;
    } else {
      var_dump("Impossible to load welcometo class : $class");
    }
  }
};
spl_autoload_register($swdNamespaceAutoload, true, true);

require_once APP_GAMEMODULE_PATH . 'module/table/table.game.php';

class welcometo extends Table
{
  use WTO\States\TurnTrait;
  use WTO\States\WriteNumberTrait;
  use WTO\States\ActionsTrait;
  use WTO\States\PlanValidationTrait;
  use WTO\States\ConfirmWaitTrait;
  use WTO\States\EndOfGameTrait;

  use WTO\States\Expansions\IceCreamTrait;

  public static $instance = null;
  public function __construct()
  {
    parent::__construct();
    self::$instance = $this;

    self::initGameStateLabels([
      'optionAdvanced' => OPTION_ADVANCED,
      'optionExpert' => OPTION_EXPERT,
      'optionBoard' => OPTION_BOARD,
      'currentTurn' => GLOBAL_CURRENT_TURN,
    ]);

    // EXPERIMENTAL to avoid deadlocks
    $this->bIndependantMultiactiveTable = true;
  }
  public static function get()
  {
    return self::$instance;
  }

  protected function getGameName()
  {
    return 'welcometo';
  }

  /*
   * setupNewGame:
   *  This method is called only once, when a new game is launched.
   * params:
   *  - array $players
   *  - mixed $options
   */
  protected function setupNewGame($players, $options = [])
  {
    WTO\Game\Players::setupNewGame($players);
    WTO\Game\Stats::setupNewGame();
    WTO\PlanCards::setupNewGame($players);
    WTO\ConstructionCards::setupNewGame($players);

    self::setGameStateValue('currentTurn', 1);
    $this->activeNextPlayer();
  }

  /*
   * getAllDatas:
   *  Gather all informations about current game situation (visible by the current player).
   *  The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
   */
  protected function getAllDatas()
  {
    $pId = self::getCurrentPId();
    return [
      'players' => WTO\Game\Players::getUiData(),
      'constructionCards' => WTO\ConstructionCards::getForPlayer($pId),
      'planCards' => WTO\PlanCards::getUiData(),
      'planValidations' => WTO\PlanCards::getCurrentValidations(),
      'options' => WTO\Game\Globals::getOptions(),
      'turn' => WTO\Game\Globals::getCurrentTurn(),
      'cardsLeft' => WTO\ConstructionCards::getInLocation('deck')->count(),
      'canceledNotifIds' => WTO\Game\Log::getCanceledNotifIds(),
      'nextPlayerTable' => $this->getNextPlayerTable(),
      'prevPlayerTable' => $this->getPrevPlayerTable(),
    ];
  }

  /*
   * getGameProgression:
   *  Compute and return the current game progression approximation
   *  This method is called each time we are in a game state with the "updateGameProgression" property set to true
   */
  public function getGameProgression()
  {
    return (100 * self::getGameStateValue('currentTurn')) / 33;
  }

  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $activePlayer)
  {
    // Only one player active => try to zombiepass transition
    if ($state['type'] === 'activeplayer') {
      if (array_key_exists('zombiePass', $state['transitions'])) {
        $this->gamestate->nextState('zombiePass');
        return;
      }
    }

    // Multiactive => make player non-active
    if ($state['type'] === 'multipleactiveplayer') {
      // Make sure player is in a non blocking status for role turn
      $this->gamestate->setPlayerNonMultiactive($activePlayer, '');
      return;
    }

    throw new BgaVisibleSystemException(
      'Zombie player ' . $activePlayer . ' stuck in unexpected state ' . $state['name']
    );
  }

  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
    if ($from_version <= 2012091842) {
      // ! important ! Use DBPREFIX_<table_name> for all tables
      $sql = "CREATE TABLE `DBPREFIX_playermultiactive` (
        `ma_player_id` int UNSIGNED NOT NULL,
        `ma_is_multiactive` tinyint NOT NULL DEFAULT '0'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 ";
      self::applyDbUpgradeToAllDB($sql);

      $sql = "ALTER TABLE `DBPREFIX_playermultiactive`
      ADD PRIMARY KEY (`ma_player_id`);";
      self::applyDbUpgradeToAllDB($sql);

      $sql = "INSERT INTO DBPREFIX_playermultiactive (ma_player_id,ma_is_multiactive)
      (SELECT player_id, player_is_multiactive FROM DBPREFIX_player )";
      self::applyDbUpgradeToAllDB($sql);
    }
  }

  ///////////////////////////////////////////////////////////
  // Exposing proteced method, please use at your own risk //
  ///////////////////////////////////////////////////////////

  // Exposing protected method getCurrentPlayerId
  public static function getCurrentPId()
  {
    return self::getCurrentPlayerId();
  }

  // Exposing protected method translation
  public static function translate($text)
  {
    return self::_($text);
  }
}
