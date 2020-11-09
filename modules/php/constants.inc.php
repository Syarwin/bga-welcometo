<?php

/*
 * State constants
 */
define('ST_GAME_SETUP', 1);

define('ST_NEW_TURN', 3);
define('ST_PLAYER_TURN', 4);
define('ST_APPLY_TURNS', 5);
define('ST_VALIDATE_PLANS', 6);

// Parallel flow
define('ST_CHOOSE_CARDS', 20);
define('ST_WRITE_NUMBER', 21);
define('ST_ACTION_SURVEYOR', 22);
define('ST_ACTION_ESTATE', 23);
define('ST_ACTION_BIS', 24);
define('ST_ACTION_PARK', 25);
define('ST_ACTION_POOL', 26);
define('ST_ACTION_TEMP', 27);

define('ST_CONFIRM_TURN', 30);
define('ST_WAIT_OTHERS', 31);


define('ST_COMPUTE_SCORES', 90);

define('ST_GAME_END', 99);


/*
 * Options constants
 */
define('OPTION_ADVANCED', 100);
define('OFF', 0);
define('ON', 1);


define('OPTION_EXPERT', 101);

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

define('ROUNDABOUT', 100);

// Advanced city plans
define('FULL_STREET_3', 1);
define('FULL_STREET_1', 2);
define('FIVE_BIS', 3);
define('SEVEN_TEMP', 4);
define('ALL_ENDS_BUILT', 5);

define('FULL_2PARK', 6);
define('COMPLETE_STREET', 7);
define('FULL_2POOL', 8);
define('FULL_PARK_POOL_3', 9);
define('FULL_PARK_POOL_2', 10);


/*
 * Stats
 */
define('STAT_TURNS', 100);
define('STAT_EOG_REFUSAL', 101);
define('STAT_EOG_PROJECTS', 102);
define('STAT_EOG_HOUSES', 103);

define('STAT_HOUSES', 10);
define('STAT_REFUSAL', 11);
define('STAT_PROJECTS', 15);
define('STAT_NOEFFECT', 16);

define('STAT_SURVEYOR', 17);
define('STAT_REAL_ESTATE', 18);
define('STAT_LANDSCAPER', 19);

define('STAT_POOLS', 20);
define('STAT_TEMPORARY_WORKERS', 21);
define('STAT_BIS', 22);
