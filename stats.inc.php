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
  ],

  "player" => [
    "houses_opened_number" => [
      "id" => 10,
      "name" => totranslate("Number of houses opened"),
      "type" => "int"
    ],

    "permit_refusal_number" => [
      "id" => STAT_REFUSAL,
      "name" => totranslate("Number of permit refusal received"),
      "type" => "int"
    ],

    "projects_number" => [
      "id" => STAT_PROJECTS,
      "name" => totranslate("Number of plans completed"),
      "type" => "int"
    ],

    "no_effect_number" => [
      "id" => STAT_NOEFFECT,
      "name" => totranslate("Number of turn without using the effect"),
      "type" => "int"
    ],

    "surveyor_number" => [
      "id" => STAT_SURVEYOR,
      "name" => totranslate("Number of Surveyor action"),
      "type" => "int"
    ],

    "real_estate_number" => [
      "id" => STAT_REAL_ESTATE,
      "name" => totranslate("Number of Real Estate action"),
      "type" => "int"
    ],

    "landscaper_number" => [
      "id" => STAT_LANDSCAPER,
      "name" => totranslate("Number of Landscaper action"),
      "type" => "int"
    ],

    "pool_manufacturer_number" => [
      "id" => STAT_POOLS,
      "name" => totranslate("Number of pools built"),
      "type" => "int"
    ],

    "temp_agency_number" => [
      "id" => STAT_TEMPORARY_WORKERS,
      "name" => totranslate("Number of temporary worker  hired"),
      "type" => "int"
    ],

    "bis_number" => [
      "id" => STAT_BIS,
      "name" => totranslate("Number of bis opened"),
      "type" => "int"
    ]
  ]
];
