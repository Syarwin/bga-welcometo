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


require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');
require_once('modules/WTOPlans.php');
require_once('modules/WTOConstructionCards.php');
require_once('modules/WTOTurnInstruction.php');
require_once('modules/WTOUpperSheet.php');
require_once('modules/WTOLowerSheet.php');
require_once('modules/WTOPlanValidationInstruction.php');


class welcometo extends Table
{
    function __construct()
    {
        // Your global variables labels:
        //  Here, you can assign labels to global variables you are using for this game.
        //  You can use any number of global variables with IDs between 10 and 99.
        //  If your game has options (variants), you also have to associate here a label to
        //  the corresponding ID in gameoptions.inc.php.
        // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
        parent::__construct();

        self::initGameStateLabels(array(
            "min_log" => 10,
            //    "my_second_global_variable" => 11,
            //      ...
            "advanced_variant" => 100,
            "expert_rules" => 101,
            //      ...
        ));

        $this->plans = new WTOPlans();
    }

    protected function getGameName()
    {
        // Used for translations and stuff. Please do not modify.
        return "welcometo";
    }

    /*
        setupNewGame:
        
        This method is called only once, when a new game is launched.
        In this method, you must setup the game according to the game rules, so that
        the game is ready to be played.
    */
    protected function setupNewGame($players, $options = array())
    {
        // Set the colors of the players with HTML color code
        // The default below is red/green/blue/orange/brown
        // The number of colors defined here must correspond to the maximum number of players allowed for the gams
        $gameinfos = self::getGameinfos();
        $default_colors = $gameinfos['player_colors'];

        // Create players
        // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
        $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
        $values = array();
        foreach ($players as $player_id => $player) {
            $color = array_shift($default_colors);
            $values[] = "('" . $player_id . "','$color','" . $player['player_canal'] . "','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
        self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
        self::reloadPlayersBasicInfos();

        /************ Start the game initialization *****/

        // Init global values with their initial values
        self::setGameStateInitialValue("min_log", 0);
        //self::setGameStateInitialValue( 'my_first_global_variable', 0 );

        // Init game statistics
        self::initStats();
        // (note: statistics used in this file must be defined in your stats.inc.php file)
        //self::initStat( 'table', 'table_teststat1', 0 );    // Init a table statistics
        //self::initStat( 'player', 'player_teststat1', 0 );  // Init a player statistics (for all players)

        // TODO: setup the initial game situation here
        $this->plans->setupPlans(boolval(self::getGameStateValue("advanced_variant")));
        $isSolo = (count($players) == 1);
        $this->getConstructionCardsInstance($isSolo)->setupConstructionCards(array_keys($players));
        WTOUpperSheet::setupUpperSheets($players);
        WTOLowerSheet::setupLowerSheets($players);

        // Activate first player (which is in general a good idea :) )
        $this->activeNextPlayer();

        /************ End of the game initialization *****/
    }

    protected function initStats()
    {
        self::initStat('table', 'turns_number', 0);
        self::initStat('table', 'permit_refusal_ending', 0);
        self::initStat('table', 'projects_ending', 0);
        self::initStat('table', 'all_houses_ending', 0);

        self::initStat('player', 'houses_opened_number', 0);
        self::initStat('player', 'permit_refusal_number', 0);
        self::initStat('player', 'projects_number', 0);
        self::initStat('player', 'no_effect_number', 0);
        self::initStat('player', 'surveyor_number', 0);
        self::initStat('player', 'real_estate_number', 0);
        self::initStat('player', 'landscaper_number', 0);
        self::initStat('player', 'pool_manufacturer_number', 0);
        self::initStat('player', 'temp_agency_number', 0);
        self::initStat('player', 'bis_number', 0);
    }

    /*
        getAllDatas: 
        
        Gather all informations about current game situation (visible by the current player).
        
        The method is called each time the game interface is displayed to a player, ie:
        _ when the game starts
        _ when a player refreshes the game page (F5)
    */
    protected function getAllDatas()
    {
        $result = array();

        $current_player_id = self::getCurrentPlayerId();    // !! We must only return informations visible by this player !!

        // Get information about players
        // Note: you can retrieve some extra field you added for "player" table in "dbmodel.sql" if you need it.
        $sql = "SELECT player_id id, player_score score FROM player ";
        $result['players'] = self::getCollectionFromDb($sql);
        $result['plans'] = $this->plans->getPlans();
        $result['stacks'] = $this->getConstructionCardsInstance(null, $current_player_id)->getConstructionCards();
        $result['options'] = array('advanced' => $this->isAdvancedGame(), 'expert' => $this->isExpertGame());
        $lowerSheets = WTOLowerSheet::getAllPlayerSheets();
        $upperSheets = WTOUpperSheet::getAllPlayerSheets();
        $result['upper_sheets'] = array_map(function ($upperSheet) {
            return $upperSheet->format();
        }, $upperSheets);
        $result['lower_sheets'] = $lowerSheets;
        $result['scores'] = $this->getScores($lowerSheets, $upperSheets);
        $result['last_turn_logs'] = $this->getLastTurnLogs();
        return $result;
    }

    /*
        getGameProgression:
        
        Compute and return the current game progression.
        The number returned must be an integer beween 0 (=the game just started) and
        100 (= the game is finished or almost finished).
    
        This method is called each time we are in a game state with the "updateGameProgression" property set to true 
        (see states.inc.php)
    */
    function getGameProgression()
    {
        $maxPermitRefusalsByAPlayer = WTOLowerSheet::getMaxPermitRefusal();
        $permitRefusalProgression = intval(($maxPermitRefusalsByAPlayer / 3) * 100);

        $minFreeBuilding = WTOUpperSheet::getMinFreeHouses();
        $freeBuildingProgression = intval(((33 - $minFreeBuilding) / 33) * 100);

        $maxNumberOfProjectsDoneByAPlayer = WTOLowerSheet::getMaxPlansDone();
        $projectProgression = intval(($maxNumberOfProjectsDoneByAPlayer / 3) * 100);

        return max($permitRefusalProgression, $freeBuildingProgression, $projectProgression);
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Utility functions
    ////////////    

    /*
        In this space, you can put any utility methods useful for your game logic
    */

    function getLastTurnLogs()
    {
        $minlog = self::getGameStateValue('min_log');
        $gamelogpackets = self::getObjectListFromDB("SELECT gamelog_notification FROM gamelog WHERE gamelog_packet_id > {$minlog}");
        $notifs = [];
        for ($i = 0; $i < count($gamelogpackets); $i++) {
            $notifs = array_merge($notifs, json_decode($gamelogpackets[$i]["gamelog_notification"], true));
        }
        $filteredNotifs = [];
        for ($i = 0; $i < count($notifs); $i++) {
            if (!is_array($notifs[$i]['args']))
                continue;
            if (array_key_exists('player_name', $notifs[$i]['args'])) {
                // All the notifications we are interested in showing have a player_name in them.
                $filteredNotifs[] = $notifs[$i];
            }
        }
        return $filteredNotifs;
    }

    function getScores($lowerSheets, $upperSheets, $withTieBreaker = false)
    {
        $scores = [];
        $tieBreaker = [];
        $isSolo = (self::getPlayersNumber() == 1);
        foreach ($lowerSheets as $playerId => $lowerSheet) {
            $upperSheet = $upperSheets[$playerId];
            $scores[$playerId] = $lowerSheet->computeDetailedPlayerScore($upperSheet, $isSolo);
            if ($withTieBreaker)
                $tieBreaker[$playerId] = $upperSheet->getTieBreaker();
        }
        if ($withTieBreaker)
            return array('scores' => $scores, 'tieBreakers' => $tieBreaker);
        return $scores;
    }

    function getConstructionCardsInstance($isSolo = null, $playerId = null)
    {
        // This function is a workaround around the fact that loadPlayersBasicInfo, and therefore getPlayersNumber,
        // is unavailable in __construct or in setupNewGame. Therefore, we build the instance when needed, based on how we can do it.
        $isExpert = $this->isExpertGame();
        if ($isExpert) {
            return new WTOConstructionCards(true, false, $playerId);
        }
        if (is_null($isSolo))
            $isSolo = (self::getPlayersNumber() == 1);
        if (!isset($this->constructionCards))
            $this->constructionCards = new WTOConstructionCards(false, $isSolo);
        return $this->constructionCards;
    }

    function isExpertGame()
    {
        return boolval(self::getGameStateValue("expert_rules"));
    }

    function isAdvancedGame()
    {
        return boolval(self::getGameStateValue("advanced_variant"));
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Player actions
    //////////// 

    /*
        Each time a player is doing some game action, one of the methods below is called.
        (note: each method below must match an input method in welcometo.action.php)
    */

    function registerPlayerTurn($stackNumber, $stackAction, $house, $roundabout, $permitRefusal, $action)
    {
        $playerId = self::getCurrentPlayerId();
        $constructionCardsInstance = $this->getConstructionCardsInstance(null, $playerId);
        $turnInstruction = WTOTurnInstruction::buildFromGameAction($playerId, $stackNumber, $stackAction, $house, $roundabout, $permitRefusal, $action, $constructionCardsInstance);
        $turnInstruction->checkInstructionIsValid($this->isAdvancedGame());
        $turnInstruction->saveInstruction();
        $this->gamestate->setPlayerNonMultiactive($playerId, "applyTurns");
    }

    function registerPlanValidation($projectId, $houseEstates)
    {
        $playerId = self::getCurrentPlayerId();
        $planValidationInstruction = new WTOPlanValidationInstruction($playerId, $projectId, $houseEstates, null);
        self::dump("registerPlanValidation", $planValidationInstruction);
        $planValidationInstruction->checkInstructionIsValid();
        $planValidationInstruction->saveInstruction();

        if (WTOPlanValidationInstruction::canPlayerSubmitAnotherProject($playerId)) {
            // Notify new project to do.
        } else {
            $this->gamestate->setPlayerNonMultiactive($playerId, "applyPlansValidation");
        }
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state arguments
    ////////////

    /*
        Here, you can create methods defined as "game state arguments" (see "args" property in states.inc.php).
        These methods function is to return some additional information that is specific to the current
        game state.
    */


    function argValidatePlans()
    {
        return array_map(function ($upperSheet) {
            return $upperSheet->getUnusedHousingEstates();
        }, WTOUpperSheet::getAllPlayerSheets());
    }

    function argPlayerTurn()
    {
        $canPlayPerPlayer = [];
        $upperSheets = WTOUpperSheet::getAllPlayerSheets();
        foreach ($upperSheets as $playerId => $upperSheet) {
            $availableNumbers = $this->getConstructionCardsInstance(null, $playerId)->getAvailableNumbers();
            $canPlayPerPlayer[$playerId] = array('canPlay' => $upperSheet->canPlay($availableNumbers), 'streetParts' => $upperSheet->getStreetsParts());
        }

        return array(
            '_private' => $canPlayPerPlayer,
        );
    }


    //////////////////////////////////////////////////////////////////////////////
    //////////// Game state actions
    ////////////

    /*
        Here, you can create methods defined as "game state actions" (see "action" property in states.inc.php).
        The action method of state X is called everytime the current game state is set to X.
    */

    function stNewTurn()
    {
        $players = self::loadPlayersBasicInfos();

        if ($this->isExpertGame()) {
            $nextPlayerTable = $this->getNextPlayerTable();
            $instructions = WTOTurnInstruction::loadInstructionsFromDB($this->getConstructionCardsInstance());
            foreach ($instructions as $playerId => $turnInstruction) {
                $turnInstruction->constructionCards->setPlayerIdAndItsStacks($playerId);
                $turnInstruction->prepareGiveCard($nextPlayerTable[$playerId]);
            }
            foreach ($players as $playerId => $player) {
                extract($this->getConstructionCardsInstance(null, $playerId)->drawNewCards());
                self::notifyPlayer($playerId, "newCards", clienttranslate('New construction cards have been flipped'), array(
                    'cards' => $drawnCards
                ));
            }
        } else {
            extract($this->getConstructionCardsInstance()->drawNewCards());
            self::notifyAllPlayers("newCards", clienttranslate('New construction cards have been flipped'), array(
                'cards' => $drawnCards
            ));
        }
        WTOTurnInstruction::cleanTurnInstructions();

        self::incStat(1, 'turns_number', null);
        foreach ($players as $playerId => $player) {
            $this->giveExtraTime($playerId);
        }

        if ($soloCardDrawn) {
            $this->plans->validateAllPlans();
            self::notifyAllPlayers("soloCardDrawn", clienttranslate('The solo card as been drawn : All the plans are considered as approved by the competition.'), array());
        }

        //Compute scores
        $lowerSheets = WTOLowerSheet::getAllPlayerSheets();
        $upperSheets = WTOUpperSheet::getAllPlayerSheets();
        $scores = $this->getScores($lowerSheets, $upperSheets);
        foreach ($scores as $playerId => $score) {
            self::DbQuery("UPDATE player SET player_score={$score['total']['total']} WHERE player_id='{$playerId}'");
        }
        self::notifyAllPlayers("scoresUpdated", "", array(
            'scores' => $scores
        ));

        if ($this->isExpertGame()) {
            // Players will have to at least select which cards they want to discard.
            // Knowledge about permit refusal is sent during next state through its arg.
            $activePlayers = array_keys($players);
        } else {
            $activePlayers = [];
            $availableNumbers = $this->getConstructionCardsInstance()->getAvailableNumbers();
            foreach ($upperSheets as $playerId => $upperSheet) {
                if ($upperSheet->canPlay($availableNumbers))
                    $activePlayers[] = $playerId;
                else {
                    WTOLowerSheet::addPermitRefusal($playerId);
                    self::incStat(1, 'permit_refusal_number', $playerId);
                    self::notifyAllPlayers("permitRefusal", clienttranslate('Player ${player_name} cannot play and received a permit refusal.'), array(
                        'player_id' => $playerId,
                        'player_name' => self::loadPlayersBasicInfos()[$playerId]['player_name'],
                    ));
                }
            }
        }
        // FYI : setPlayersMultiactive returns true is it did the transition ($activePlayers is empty), false otherwise.
        if (!$this->gamestate->setPlayersMultiactive($activePlayers, "checkEndGameConditions"))
            $this->gamestate->nextState('playerTurn');
    }

    function stApplyTurn()
    {

        self::setGameStateValue("min_log", self::getUniqueValueFromDB("SELECT MAX(gamelog_packet_id) FROM gamelog"));
        $instructions = WTOTurnInstruction::loadInstructionsFromDB($this->getConstructionCardsInstance());
        foreach ($instructions as $playerId => $turnInstruction) {
            if ($this->isExpertGame())
                $turnInstruction->constructionCards->setPlayerIdAndItsStacks($playerId);
            $applyResponse = $turnInstruction->applyToSheet();
            $notifications = $applyResponse['notifications'];
            $stats = $applyResponse['stats'];
            foreach ($notifications as $key => $notification) {
                $notification[2]['player_name'] = self::loadPlayersBasicInfos()[$playerId]['player_name'];
                $notification[2]['player_id'] = $playerId;
                self::notifyAllPlayers(...$notification);
            }
            foreach ($stats as $key => $stat) {
                self::incStat($stat['incNumber'], $stat['label'], $stat['playerId']);
            }
        }
        $this->gamestate->nextState('validatePlans');
    }

    function stValidatePlans()
    {
        // Check which plans / players can be done.
        $upperSheets = WTOUpperSheet::getAllPlayerSheets();
        $lowerSheets = WTOLowerSheet::getAllPlayerSheets();
        $plansStatus = [];
        foreach ($upperSheets as $playerId => $upperSheet) {
            $plansStatus[$playerId] = $this->plans->checkPlans($upperSheet, $lowerSheets[$playerId]->getAlreadyDonePlans());
        }
        $players = [];
        foreach ($plansStatus as $playerId => $checkPlansResult) {
            $canBeValidatedPlans = array_reduce($checkPlansResult, function ($count, $plan) {
                if ($plan['validated'])
                    return $count + 1;
                return $count;
            }, 0);
            if ($canBeValidatedPlans >= 1) {
                $players[] = $playerId;
            }
        }
        $this->gamestate->setPlayersMultiactive($players, "applyPlansValidation");
    }

    function stApplyPlans()
    {
        $planValidationInstructions = WTOPlanValidationInstruction::loadInstructionsFromDB();
        self::dump("stApplyPlans", $planValidationInstructions);
        $notifications = [];
        foreach ($planValidationInstructions as $key => $planInstruction) {
            $notification = $planInstruction->applyInstruction();
            $notification[2]['player_name'] = self::loadPlayersBasicInfos()[$planInstruction->playerId]['player_name'];
            $notifications[] = $notification;
            self::incStat(1, 'projects_number', $planInstruction->playerId);
        }
        foreach ($notifications as $key => $notification) {
            self::notifyAllPlayers(...$notification);
        }

        WTOPlanValidationInstruction::cleanPlanValidationInstructions();
        $this->gamestate->nextState('checkEndGameConditions');
    }

    function stCheckEndGameConditions()
    {
        $endGame = false;
        $lowerSheets = WTOLowerSheet::getAllPlayerSheets();
        $upperSheets = WTOUpperSheet::getAllPlayerSheets();
        foreach ($lowerSheets as $playerId => $lowerSheet) {
            if ($lowerSheet->hasAchievedAllPlans()) {
                self::setStat(1, 'projects_ending', null);
                $endGame = true;
            }
            if ($lowerSheet->hasThreePermitRefusals()) {
                self::setStat(1, 'permit_refusal_ending', null);
                $endGame = true;
            }
            $upperSheet = $upperSheets[$playerId];
            if ($upperSheet->hasBuiltAllHouses()) {
                self::setStat(1, 'all_houses_ending', null);
                $endGame = true;
            }
        }
        if ($endGame)
            $this->gamestate->nextState('computeScores');
        else
            $this->gamestate->nextState('newTurn');
    }

    function stComputeScores()
    {
        $lowerSheets = WTOLowerSheet::getAllPlayerSheets();
        $upperSheets = WTOUpperSheet::getAllPlayerSheets();
        $scoresWithTieBreakers = $this->getScores($lowerSheets, $upperSheets, true);
        $scores = $scoresWithTieBreakers['scores'];
        $tieBreaker = $scoresWithTieBreakers['tieBreakers'];

        $sortedtieBreakers = array_values($tieBreaker);
        rsort($sortedtieBreakers);
        foreach ($tieBreaker as $playerId => $playerTieBreaker) {
            $tieBreakerRanking = count($tieBreaker) - array_search($playerTieBreaker, $sortedtieBreakers);
            self::DbQuery("UPDATE player SET player_score={$scores[$playerId]['total']['total']}, player_score_aux={$tieBreakerRanking} WHERE player_id='{$playerId}'");
        }

        self::notifyAllPlayers("scoresUpdated", "", array(
            'scores' => $scores
        ));
        $this->gamestate->nextState('endGame');
    }

    //////////////////////////////////////////////////////////////////////////////
    //////////// Zombie
    ////////////

    /*
        zombieTurn:
        
        This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
        You can do whatever you want in order to make sure the turn of this player ends appropriately
        (ex: pass).
        
        Important: your zombie code will be called when the player leaves the game. This action is triggered
        from the main site and propagated to the gameserver from a server, not from a browser.
        As a consequence, there is no current player associated to this action. In your zombieTurn function,
        you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message. 
    */

    function zombieTurn($state, $active_player)
    {
        $statename = $state['name'];

        if ($state['type'] === "activeplayer") {
            switch ($statename) {
                default:
                    $this->gamestate->nextState("zombiePass");
                    break;
            }

            return;
        }

        if ($state['type'] === "multipleactiveplayer") {
            // Make sure player is in a non blocking status for role turn
            $this->gamestate->setPlayerNonMultiactive($active_player, '');

            return;
        }

        throw new feException("Zombie mode not supported at this game state: " . $statename);
    }

    ///////////////////////////////////////////////////////////////////////////////////:
    ////////// DB upgrade
    //////////

    /*
        upgradeTableDb:
        
        You don't have to care about this until your game has been published on BGA.
        Once your game is on BGA, this method is called everytime the system detects a game running with your old
        Database scheme.
        In this case, if you change your Database scheme, you just have to apply the needed changes in order to
        update the game database and allow the game to continue to run with your new version.
    
    */

    function upgradeTableDb($from_version)
    {
        // $from_version is the current version of this game database, in numerical form.
        // For example, if the game was running with a release of your game named "140430-1345",
        // $from_version is equal to 1404301345

        // Example:
        //        if( $from_version <= 1404301345 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
        //            self::applyDbUpgradeToAllDB( $sql );
        //        }
        //        if( $from_version <= 1405061421 )
        //        {
        //            // ! important ! Use DBPREFIX_<table_name> for all tables
        //
        //            $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
        //            self::applyDbUpgradeToAllDB( $sql );
        //        }
        //        // Please add your future database scheme changes here
        //
        //


    }

    function debugging()
    {
        // WTOUpperSheet::getPlayerSheet(2316488)->checkCanPlaceHouse(2, 3);
        WTOUpperSheet::buildHouse(2316488, 0, 1);
        WTOUpperSheet::buildHouse(2316488, 1, 3);
        WTOUpperSheet::buildFence(2316488, 1);

        WTOUpperSheet::buildHouse(2316488, 10, 2);
        WTOUpperSheet::buildHouse(2316488, 11, 3);
        WTOUpperSheet::buildHouse(2316488, 12, 6);
        WTOUpperSheet::buildFence(2316488, 11);
        WTOUpperSheet::buildFence(2316488, 12);

        WTOUpperSheet::buildHouse(2316488, 21, 1);
        WTOUpperSheet::buildHouse(2316488, 22, 2);
        WTOUpperSheet::buildHouse(2316488, 23, 4);
        WTOUpperSheet::buildHouse(2316488, 24, 7);
        WTOUpperSheet::buildHouse(2316488, 25, 8);
        WTOUpperSheet::buildHouse(2316488, 26, 10);
        WTOUpperSheet::buildFence(2316488, 26);
        self::dump("Debugging", WTOUpperSheet::getPlayerSheet(2316488)->getUnusedHousingEstatesNumberPerSize());
    }

    function testCanSubmit()
    {
        WTOPlanValidationInstruction::canPlayerSubmitAnotherProject(2316488);
    }

    function testPlanValidation()
    {
        WTOUpperSheet::buildHouse(2316488, 13, 7);
        WTOUpperSheet::buildHouse(2316488, 14, 7);
        WTOUpperSheet::buildHouse(2316488, 15, 8);
        WTOUpperSheet::buildHouse(2316488, 16, 8);
        WTOUpperSheet::buildFence(2316488, 16);
    }

    function setPlan()
    {
        self::DbQuery("UPDATE `plan_card` SET card_location = 'discard' WHERE `plan_card`.`card_location` = 'table'");
        self::DbQuery("UPDATE `plan_card` SET card_location = 'table' , card_location_arg = 0 WHERE `plan_card`.`card_type` in (18, 21, 27)");
    }

    function debugPlanValidation()
    {
        // WTOUpperSheet::buildFence(2316488, 0);
        // WTOUpperSheet::buildFence(2316488, 21);
        // WTOUpperSheet::buildFence(2316488, 25);

        // Plan 20
        WTOUpperSheet::buildHouse(2316488, 21, 1);
        WTOUpperSheet::buildHouse(2316488, 22, 2);
        WTOUpperSheet::buildHouse(2316488, 23, 4);
        WTOUpperSheet::buildHouse(2316488, 24, 7);
        WTOUpperSheet::buildHouse(2316488, 25, 8);
        WTOUpperSheet::buildHouse(2316488, 26, 10);
        WTOUpperSheet::buildHouse(2316488, 27, 500);
        WTOUpperSheet::buildHouse(2316488, 28, 2);
        WTOUpperSheet::buildHouse(2316488, 29, 4);
        WTOUpperSheet::buildHouse(2316488, 30, 7);
        WTOUpperSheet::buildHouse(2316488, 31, 8);
        WTOUpperSheet::buildHouse(2316488, 32, 10);
    }

    function debugPermitRefusal()
    {
        WTOUpperSheet::buildHouse(2316488, 0, 15);
        WTOUpperSheet::buildHouse(2316488, 10, 15);
        // WTOUpperSheet::buildHouse(2316488, 21, 15);
    }
}
