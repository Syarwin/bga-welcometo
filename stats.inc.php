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
 * stats.inc.php
 *
 * welcometo game statistics description
 *
 */

require_once('modules/php/constants.inc.php');

$stats_type = [
  "table" => [
    "turns_number" => [
      "id" => STAT_TURNS,
      "name" => totranslate("Number of turns"),
      "type" => "int"
    ],

    "ending" => [
      "id" => STAT_EOG,
      "name" => totranslate("Permit refusal ending"),
      "type" => "int"
    ],
/*
    "permit_refusal_ending" => [
      "id" => STAT_EOG_REFUSAL,
      "name" => totranslate("Permit refusal ending"),
      "type" => "bool"
    ],

    "projects_ending" => [
      "id" => STAT_EOG_PROJECTS,
      "name" => totranslate("Three plans completed ending"),
      "type" => "bool"
    ],

    "all_houses_ending" => [
      "id" => STAT_EOG_HOUSES,
      "name" => totranslate("All houses built ending"),
      "type" => "bool"
    ],
*/
  ],

  "value_labels" => [
  		STAT_EOG => [
  			0 => totranslate("None"),
  			1 => totranslate("Permit refusal ending"),
  			2 => totranslate("Three plans completed ending"),
  			3 => totranslate("All houses built ending"),
  			4 => totranslate("End of deck ending (solo mode)"),
  		]
  ],

  "player" => [
    "houses_built_number" => [
      "id" => STAT_HOUSES,
      "name" => totranslate("Number of houses built"),
      "type" => "int"
    ],
    "roundabouts_built_number" => [
      "id" => STAT_ROUNDABOUTS,
      "name" => totranslate("Number of roundabouts built"),
      "type" => "int"
    ],
    "empty_slots_number" => [
      "id" => STAT_EMPTY_SLOT,
      "name" => totranslate("Number of empty slots in your streets"),
      "type" => "int"
    ],
    "permit_refusal_number" => [
      "id" => STAT_REFUSAL,
      "name" => totranslate("Number of permit refusal received"),
      "type" => "int"
    ],
    "projects_number_first" => [
      "id" => STAT_PROJECTS_FIRST,
      "name" => totranslate("Number of plans completed first"),
      "type" => "int"
    ],
    "projects_number_second" => [
      "id" => STAT_PROJECTS_SECOND,
      "name" => totranslate("Number of plans completed second"),
      "type" => "int"
    ],

    "selected_surveyor_number" => [
      "id" => STAT_SURVEYOR_SELECTED,
      "name" => totranslate("Number of Surveyor cards selected"),
      "type" => "int"
    ],
    "selected_real_estate_number" => [
      "id" => STAT_REAL_ESTATE_SELECTED,
      "name" => totranslate("Number of Real Estate cards selected"),
      "type" => "int"
    ],
    "selected_landscaper_number" => [
      "id" => STAT_LANDSCAPER_SELECTED,
      "name" => totranslate("Number of Landscaper cards selected"),
      "type" => "int"
    ],
    "selected_pool_manufacturer_number" => [
      "id" => STAT_POOLS_SELECTED,
      "name" => totranslate("Number of pool cards selected"),
      "type" => "int"
    ],
    "selected_temp_agency_number" => [
      "id" => STAT_TEMPORARY_WORKERS_SELECTED,
      "name" => totranslate("Number of temp cards selected"),
      "type" => "int"
    ],
    "selected_bis_number" => [
      "id" => STAT_BIS_SELECTED,
      "name" => totranslate("Number of bis cards selected"),
      "type" => "int"
    ],


    "used_surveyor_number" => [
      "id" => STAT_SURVEYOR_USED,
      "name" => totranslate("Number of Surveyor action taken"),
      "type" => "int"
    ],
    "used_real_estate_number" => [
      "id" => STAT_REAL_ESTATE_USED,
      "name" => totranslate("Number of Real Estate action taken"),
      "type" => "int"
    ],
    "used_landscaper_number" => [
      "id" => STAT_LANDSCAPER_USED,
      "name" => totranslate("Number of Landscaper action taken"),
      "type" => "int"
    ],
    "used_pool_manufacturer_number" => [
      "id" => STAT_POOLS_USED,
      "name" => totranslate("Number of pools built"),
      "type" => "int"
    ],
    "used_temp_agency_number" => [
      "id" => STAT_TEMPORARY_WORKERS_USED,
      "name" => totranslate("Number of temporary worker hired"),
      "type" => "int"
    ],
    "used_bis_number" => [
      "id" => STAT_BIS_USED,
      "name" => totranslate("Number of bis opened"),
      "type" => "int"
    ],


    "size_1_estates" => [
      "id" => STAT_ESTATE_1,
      "name" => totranslate("Number of size 1 housing estates"),
      "type" => "int"
    ],
    "size_2_estates" => [
      "id" => STAT_ESTATE_2,
      "name" => totranslate("Number of size 2 housing estates"),
      "type" => "int"
    ],
    "size_3_estates" => [
      "id" => STAT_ESTATE_3,
      "name" => totranslate("Number of size 3 housing estates"),
      "type" => "int"
    ],
    "size_4_estates" => [
      "id" => STAT_ESTATE_4,
      "name" => totranslate("Number of size 4 housing estates"),
      "type" => "int"
    ],
    "size_5_estates" => [
      "id" => STAT_ESTATE_5,
      "name" => totranslate("Number of size 5 housing estates"),
      "type" => "int"
    ],
    "size_6_estates" => [
      "id" => STAT_ESTATE_6,
      "name" => totranslate("Number of size 6 housing estates"),
      "type" => "int"
    ],


    "scoring_plan" => [
      "id" => STAT_SCORING_PLAN,
      "name" => totranslate("Points from City Plans"),
      "type" => "int"
    ],
    "scoring_park" => [
      "id" => STAT_SCORING_PARK,
      "name" => totranslate("Points from Parks"),
      "type" => "int"
    ],
    "scoring_pool" => [
      "id" => STAT_SCORING_POOL,
      "name" => totranslate("Points from Pools"),
      "type" => "int"
    ],
    "scoring_temp" => [
      "id" => STAT_SCORING_TEMP,
      "name" => totranslate("Points from Temp"),
      "type" => "int"
    ],
    "scoring_estate_1" => [
      "id" => STAT_SCORING_ESTATE_1,
      "name" => totranslate("Points from size 1 housing estates"),
      "type" => "int"
    ],
    "scoring_estate_2" => [
      "id" => STAT_SCORING_ESTATE_2,
      "name" => totranslate("Points from size 2 housing estates"),
      "type" => "int"
    ],
    "scoring_estate_3" => [
      "id" => STAT_SCORING_ESTATE_3,
      "name" => totranslate("Points from size 3 housing estates"),
      "type" => "int"
    ],
    "scoring_estate_4" => [
      "id" => STAT_SCORING_ESTATE_4,
      "name" => totranslate("Points from size 4 housing estates"),
      "type" => "int"
    ],
    "scoring_estate_5" => [
      "id" => STAT_SCORING_ESTATE_5,
      "name" => totranslate("Points from size 5 housing estates"),
      "type" => "int"
    ],
    "scoring_estate_6" => [
      "id" => STAT_SCORING_ESTATE_6,
      "name" => totranslate("Points from size 6 housing estates"),
      "type" => "int"
    ],
    "scoring_estate_total" => [
      "id" => STAT_SCORING_ESTATE_TOTAL,
      "name" => totranslate("Total points from housing estates"),
      "type" => "int"
    ],
    "scoring_bis" => [
      "id" => STAT_SCORING_BIS,
      "name" => totranslate("Points from Bis"),
      "type" => "int"
    ],
    "scoring_roundabout" => [
      "id" => STAT_SCORING_ROUNDABOUT,
      "name" => totranslate("Points from Roundabouts"),
      "type" => "int"
    ],
    "scoring_permit_refusal" => [
      "id" => STAT_SCORING_PERMIT_REFUSAL,
      "name" => totranslate("Points from Permit Refusals"),
      "type" => "int"
    ],

  ]

];
