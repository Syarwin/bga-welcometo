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
 * states.inc.php
 *
 * welcometo game states description
 *
 */

$machinestates = [
  /*
   * BGA framework initial state. Do not modify.
   */
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    'transitions' => [
      '' => ST_NEW_TURN,
    ],
  ],

  ST_NEW_TURN => [
    "name" => "newTurn",
    "description" => clienttranslate('A new turn is starting'),
    "type" => "game",
    "updateGameProgression" => true,
    "action" => "stNewTurn",
    "transitions" => [
      "playerTurn" => ST_PLAYER_TURN,
    ]
  ],

  ST_PLAYER_TURN => [
    "name" => "playerTurn",
    "description" => clienttranslate('Waiting for other players to end their turn.'),
    "descriptionmyturn" => clienttranslate('${you} must pick a pair of construction cards'), // Won't be displayed anyway
    "type" => "multipleactiveplayer",
    "parallel" => ST_CHOOSE_CARDS, // Allow to have parallel flow for each player
    "action" => "stPlayerTurn",
    "args" => "argPlayerTurn",
    "possibleactions" => ["registerPlayerTurn"],
    "transitions" => ["applyTurns" => ST_APPLY_TURNS]
  ],



/****************************
***** PARALLEL STATES *******
****************************/

  ST_CHOOSE_CARDS => [
    "name" => "chooseCards",
    "descriptionmyturn" => clienttranslate('${you} must pick a pair of construction cards'),
    "type" => "private",
    "args" => "argChooseCards",
    "possibleactions" => ["chooseCards", "refusal", "roundabout"],
    "transitions" => [
      'writeNumber' => ST_WRITE_NUMBER,
      'refusal' => ST_CHOOSE_PLAN,
      'roundabout' => ST_ROUNDABOUT,
    ]
  ],


  ST_WRITE_NUMBER => [
    "name" => "writeNumber",
    "descriptionmyturn" => clienttranslate('${you} must write a number on a house'),
    "type" => "private",
    "args" => "argWriteNumber",
    "possibleactions" => ["writeNumber", "restart"],
    "transitions" => [
      SURVEYOR => ST_ACTION_SURVEYOR,
      ESTATE   => ST_ACTION_ESTATE,
      PARK     => ST_ACTION_PARK,
      POOL     => ST_ACTION_POOL,
      TEMP     => ST_ACTION_TEMP,
      BIS      => ST_ACTION_BIS,
      'restart' => ST_CHOOSE_CARDS,
    ]
  ],


  ST_ROUNDABOUT => [
    "name" => "buildRoundabout",
    "descriptionmyturn" => clienttranslate('${you} may create a roundabout in a empty house\'s box'),
    "type" => "private",
    "args" => "argRoundabout",
    "possibleactions" => ["writeNumber", "pass"],
    "transitions" => [
      'pass' => ST_CHOOSE_CARDS,
      'built' => ST_CHOOSE_CARDS,
    ]
  ],


  ////////////////////
  ///// ACTIONS //////
  ////////////////////

  ST_ACTION_SURVEYOR => [
    "name" => "actionSurveyor",
    "descriptionmyturn" => clienttranslate('${you} may build a fence between two houses'),
    "type" => "private",
    "args" => "argActionSurveyor",
    "possibleactions" => ["scribbleZone", "pass", "restart"],
    "transitions" => [
      'scribbleZone' => ST_CHOOSE_PLAN,
      'pass' => ST_CHOOSE_PLAN,
      'restart' => ST_CHOOSE_CARDS,
    ]
  ],

  ST_ACTION_ESTATE => [
    "name" => "actionEstate",
    "descriptionmyturn" => clienttranslate('${you} may increase the value of completed housing estates'),
    "type" => "private",
    "args" => "argActionEstate",
    "possibleactions" => ["scribbleZone", "pass", "restart"],
    "transitions" => [
      'scribbleZone' => ST_CHOOSE_PLAN,
      'pass' => ST_CHOOSE_PLAN,
      'restart' => ST_CHOOSE_CARDS,
    ]
  ],

  ST_ACTION_TEMP => [
    "name" => "actionTemp",
    "descriptionmyturn" => clienttranslate('${you} automatically cross one box from the temp agency column'),
    "action" => "stActionTemp",
    "type" => "private",
    "transitions" => [
      'scribbleZone' => ST_CHOOSE_PLAN,
    ]
  ],


  ST_ACTION_PARK => [
    "name" => "actionPark",
    "descriptionmyturn" => clienttranslate('${you} may build a park'),
    "type" => "private",
    "action" => "stActionPark",
    "args" => "argActionPark",
    "possibleactions" => ["scribbleZone", "pass", "restart"],
    "transitions" => [
      'scribbleZone' => ST_CHOOSE_PLAN,
      'pass' => ST_CHOOSE_PLAN,
      'restart' => ST_CHOOSE_CARDS,
    ]
  ],

  ST_ACTION_POOL => [
    "name" => "actionPool",
    "descriptionmyturn" => clienttranslate('${you} may build a pool'),
    "type" => "private",
    "action" => "stActionPool",
    "args" => "argActionPool",
    "possibleactions" => ["scribbleZone", "pass", "restart"],
    "transitions" => [
      'scribbleZone' => ST_CHOOSE_PLAN,
      'pass' => ST_CHOOSE_PLAN,
      'restart' => ST_CHOOSE_CARDS,
    ]
  ],


  ST_ACTION_BIS => [
    "name" => "actionBis",
    "descriptionmyturn" => clienttranslate('${you} may duplicate a house number'),
    "type" => "private",
    "args" => "argActionBis",
    "possibleactions" => ["writeNumberBis", "pass", "restart"],
    "transitions" => [
      'bis' => ST_CHOOSE_PLAN,
      'pass' => ST_CHOOSE_PLAN,
      'restart' => ST_CHOOSE_CARDS,
    ]
  ],



  ////////////////////
  ////// PLANS ///////
  ////////////////////

  ST_CHOOSE_PLAN => [
    "name" => "choosePlan",
    "descriptionmyturn" => clienttranslate('${you} may select a plan to validate'),
    "type" => "private",
    "action" => "stChoosePlan",
    "args" => "argChoosePlan",
    "possibleactions" => ["choosePlan", "pass", "restart"],
    "transitions" => [
      'none' => ST_CONFIRM_TURN,
      'pass' => ST_CONFIRM_TURN,
      'validatePlan' => ST_VALIDATE_PLAN,
      'restart' => ST_CHOOSE_CARDS,
    ]
  ],



  ST_VALIDATE_PLAN => [
    "name" => "validatePlan",
    "descriptionmyturn" => clienttranslate('${you} must select estates to validate this plan'),
    "type" => "private",
    "action" => "stValidatePlan",
    "args" => "argValidatePlan",
    "possibleactions" => ["validatePlan", "restart"],
    "transitions" => [
      'restart' => ST_CHOOSE_CARDS,
      'reshuffle' => ST_ASK_RESHUFFLE,
    ]
  ],


  ST_ASK_RESHUFFLE => [
    "name" => "askReshuffle",
    "descriptionmyturn" => clienttranslate('${you} may reshuffle the deck of cards'),
    "type" => "private",
    "action" => "stAskReshuffle",
    "possibleactions" => ["pass", "reshuffle"],
    "transitions" => [
      'pass' => ST_CHOOSE_PLAN,
      'reshuffle' => ST_CHOOSE_PLAN,
    ]
  ],


  //////////////////////////
  ///// CONFIRM / END //////
  //////////////////////////

  // Pre-end of parallel flow
  ST_CONFIRM_TURN => [
    "name" => "confirmTurn",
    "descriptionmyturn" => clienttranslate('${you} must confirm or restart your turn'),
    "type" => "private",
    "args" => "argPrivatePlayerTurn",
    "possibleactions" => ["confirm", "restart"],
    "transitions" => [
      'confirm' => ST_WAIT_OTHERS,
      'restart' => ST_CHOOSE_CARDS,
    ]
  ],

  // Waiting other
  ST_WAIT_OTHERS => [
    "name" => "waitOthers",
    "descriptionmyturn" => '',
    "type" => "private",
    "action" => "stWaitOther",
    "args" => "argPrivatePlayerTurn",
    "possibleactions" => ["restart"],
    "transitions" => [
      'restart' => ST_CHOOSE_CARDS,
    ]
  ],

/****************************
****************************/

  ST_APPLY_TURNS => [
    "name" => "applyTurns",
    "description" => clienttranslate('Here is what each player has done during this turn.'),
    "type" => "game",
    "action" => "stApplyTurn",
    "transitions" => [
      "newTurn" => ST_NEW_TURN,
      "endGame" => ST_COMPUTE_SCORES,
    ]
  ],





/****************************
********* END OF GAME *******
****************************/

  ST_COMPUTE_SCORES => [
    "name" => "computeScores",
    "description" => clienttranslate('Let\'s compute the scores and tie breakes'),
    "type" => "game",
    "action" => "stComputeScores",
    "transitions" => [
      "endGame" => ST_GAME_END
    ]
  ],


  /*
   * BGA framework final state. Do not modify.
   */
  ST_GAME_END => [
    'name' => 'gameEnd',
    'description' => clienttranslate('End of game'),
    'type' => 'manager',
    'action' => 'stGameEnd',
    'args' => 'argGameEnd'
  ]
];
