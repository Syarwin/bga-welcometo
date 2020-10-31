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
//      "checkEndGameConditions" => 8 TODO WEIRD
    ]
  ],

  ST_PLAYER_TURN => [
    "name" => "playerTurn",
    "description" => clienttranslate('Waiting for other players to end their turn.'),
    "descriptionmyturn" => clienttranslate('${you} must pick a pair of construction cards'),
    "type" => "multipleactiveplayer",
    "action" => "stPlayerTurn",
    "args" => "argPlayerTurn",
    "possibleactions" => ["registerPlayerTurn"],
    "transitions" => ["applyTurns" => ST_APPLY_TURNS]
  ],

  ST_APPLY_TURNS => [
    "name" => "applyTurns",
    "description" => clienttranslate('Here is what each player has done during this turn.'),
    "type" => "game",
    "action" => "stApplyTurn",
    "transitions" => [
      "newTurn" => ST_NEW_TURN,
      "validatePlans" => ST_VALIDATE_PLANS
    ]
  ],

  ST_VALIDATE_PLANS => [
    "name" => "validatePlans",
    "description" => clienttranslate('Some players can validate their plans.'),
    "descriptionmyturn" => clienttranslate('${you} must decide which plan to validate, and which housing estate must be used for it if the plan doesn\'t have an asterisk (not an advanced one).'),
    "type" => "multipleactiveplayer",
    "action" => "stValidatePlans",
    "args" => "argValidatePlans",
    "possibleactions" => "validatePlans",
    "transitions" => [
//      "applyPlansValidation" => ST_APPLY_ TODO : weird
    ]
  ],

/*
    7 => array(
        "name" => "applyPlansValidation",
        "description" => clienttranslate('Some players can validate their plans.'),
        "descriptionmyturn" => clienttranslate('${you} must decide which plan to validate, and which housing estate must be used for it if the plan doesn\'t have an asterisk (not an advanced one).'),
        "type" => "multipleactiveplayer",
        "action" => "stApplyPlans",
        "transitions" => array("checkEndGameConditions" => 8)
    ),
*/

/* TODO : weird
    8 => array(
        "name" => "checkEndGameConditions",
        "description" => clienttranslate('Is it the end?'),
        "type" => "game",
        "action" => "stCheckEndGameConditions",
        "transitions" => array("newTurn" => 2, "computeScores" => 98)
    ),
*/

  ST_COMPUTE_SCORES => [
    "name" => "computeScores",
    "description" => clienttranslate('Let\'s compute the scores and tie breakes'),
    "type" => "game",
    "action" => "stComputeScores",
    "transitions" => ["endGame" => ST_GAME_END]
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
