<?php

/*
 * State constants
 */
define('ST_GAME_SETUP', 1);

define('ST_NEW_TURN', 3);
define('ST_PLAYER_TURN', 4);
define('ST_APPLY_TURNS', 5);

// Parallel flow
define('ST_CHOOSE_CARDS', 20);
define('ST_WRITE_NUMBER', 21);
define('ST_ACTION_SURVEYOR', 22);
define('ST_ACTION_ESTATE', 23);
define('ST_ACTION_BIS', 24);
define('ST_ACTION_PARK', 25);
define('ST_ACTION_POOL', 26);
define('ST_ACTION_TEMP', 27);

define('ST_CHOOSE_PLAN', 30);
define('ST_VALIDATE_PLAN', 31);
define('ST_ASK_RESHUFFLE', 32);

define('ST_CONFIRM_TURN', 40);
define('ST_WAIT_OTHERS', 41);

define('ST_ROUNDABOUT', 50);

define('ST_ICE_CREAM', 60);



define('ST_COMPUTE_SCORES', 90);

define('ST_GAME_END', 99);


/*
 * Options constants
 */
define('OPTION_ADVANCED', 100);
define('OFF', 0);
define('ON', 1);


define('OPTION_EXPERT', 101);


define('OPTION_BOARD', 102);
define('OPTION_BOARD_BASE', 0);
define('OPTION_BOARD_ICE_CREAM', 1);


/*
 * User preference
 */
define('AUTOMATIC', 101);
define('DISABLED', 1);
define('ENABLED', 2);

define('CONFIRM', 102);
define('CONFIRM_TIMER', 1);
define('CONFIRM_ENABLED', 2);
define('CONFIRM_DISABLED', 3);

define('END_OF_TURN_ANIMATION', 103);


/*
 * Global game variables
 */
define('GLOBAL_CURRENT_TURN', 20);


define('SURVEYOR', 1);
define('ESTATE', 2);
define('PARK', 3);
define('POOL', 4);
define('TEMP', 5);
define('BIS', 6);
define('SOLO', 7);

define('ACTION_NAMES', [
  SURVEYOR => clienttranslate("Surveyor"),
  ESTATE   => clienttranslate("Real Estate Agent"),
  PARK     => clienttranslate("Landscaper"),
  POOL     => clienttranslate("Pool Manufacturer"),
  TEMP     => clienttranslate("Temp agency"),
  BIS      => clienttranslate("Bis")
]);


define('ROUNDABOUT', 100);

/*
 * City plans
 */
define('BASIC', 1);
define('ADVANCED', 2);

/*
 * Stats
 */
define('STAT_TURNS', 105);
define('STAT_EOG', 109);


define('STAT_HOUSES', 30);
define('STAT_ROUNDABOUTS', 31);
define('STAT_EMPTY_SLOT', 32);
define('STAT_REFUSAL', 33);
define('STAT_PROJECTS_FIRST', 34);
define('STAT_PROJECTS_SECOND', 35);

define('STAT_SURVEYOR_SELECTED', 40);
define('STAT_SURVEYOR_USED', 41);

define('STAT_REAL_ESTATE_SELECTED', 42);
define('STAT_REAL_ESTATE_USED', 43);

define('STAT_LANDSCAPER_SELECTED', 44);
define('STAT_LANDSCAPER_USED', 45);

define('STAT_POOLS_SELECTED', 46);
define('STAT_POOLS_USED', 47);

define('STAT_TEMPORARY_WORKERS_SELECTED', 48);
define('STAT_TEMPORARY_WORKERS_USED', 49);

define('STAT_BIS_SELECTED', 50);
define('STAT_BIS_USED', 51);


define('STAT_ESTATE_1', 60);
define('STAT_ESTATE_2', 61);
define('STAT_ESTATE_3', 62);
define('STAT_ESTATE_4', 63);
define('STAT_ESTATE_5', 64);
define('STAT_ESTATE_6', 65);


define('STAT_SCORING_PLAN', 70);
define('STAT_SCORING_PARK', 71);
define('STAT_SCORING_POOL', 72);
define('STAT_SCORING_TEMP', 73);
define('STAT_SCORING_ESTATE_1', 74);
define('STAT_SCORING_ESTATE_2', 75);
define('STAT_SCORING_ESTATE_3', 76);
define('STAT_SCORING_ESTATE_4', 77);
define('STAT_SCORING_ESTATE_5', 78);
define('STAT_SCORING_ESTATE_6', 79);
define('STAT_SCORING_ESTATE_TOTAL', 80);
define('STAT_SCORING_BIS', 81);
define('STAT_SCORING_ROUNDABOUT', 82);
define('STAT_SCORING_PERMIT_REFUSAL', 83);
