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

/*
    In this file, you are describing game statistics, that will be displayed at the end of the
    game.
    
    !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
    ("Control Panel" / "Manage Game" / "Your Game")
    
    There are 2 types of statistics:
    _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
    _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

    Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean
    
    Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
    in your game logic, using statistics names defined below.
    
    !! It is not a good idea to modify this file when a game is running !!

    If your game is already public on BGA, please read the following before any change:
    http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress
    
    Notes:
    * Statistic index is the reference used in setStat/incStat/initStat PHP method
    * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
    * Statistics IDs must be >=10
    * Two table statistics can't share the same ID, two player statistics can't share the same ID
    * A table statistic can have the same ID than a player statistics
    * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
    * Statistic name is the English description of the statistic as shown to players
    
*/

$stats_type = array(

    // Statistics global to table
    "table" => array(

        "turns_number" => array(
            "id" => 100,
            "name" => totranslate("Number of turns"),
            "type" => "int"
        ),

        "permit_refusal_ending" => array(
            "id" => 101,
            "name" => totranslate("Permit refusal ending"),
            "type" => "bool"
        ),

        "projects_ending" => array(
            "id" => 102,
            "name" => totranslate("Three plans completed ending"),
            "type" => "bool"
        ),

        "all_houses_ending" => array(
            "id" => 103,
            "name" => totranslate("All houses built ending"),
            "type" => "bool"
        ),

        /*
        Examples:


        "table_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("table test stat 1"), 
                                "type" => "int" ),
                                
        "table_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("table test stat 2"), 
                                "type" => "float" )
*/
    ),

    // Statistics existing for each player
    "player" => array(

        "houses_opened_number" => array(
            "id" => 10,
            "name" => totranslate("Number of houses opened"),
            "type" => "int"
        ),

        "permit_refusal_number" => array(
            "id" => 11,
            "name" => totranslate("Number of permit refusal received"),
            "type" => "int"
        ),

        "projects_number" => array(
            "id" => 15,
            "name" => totranslate("Number of plans completed"),
            "type" => "int"
        ),

        "no_effect_number" => array(
            "id" => 16,
            "name" => totranslate("Number of turn without using the effect"),
            "type" => "int"
        ),

        "surveyor_number" => array(
            "id" => 17,
            "name" => totranslate("Number of Surveyor action"),
            "type" => "int"
        ),

        "real_estate_number" => array(
            "id" => 18,
            "name" => totranslate("Number of Real Estate action"),
            "type" => "int"
        ),

        "landscaper_number" => array(
            "id" => 19,
            "name" => totranslate("Number of Landscaper action"),
            "type" => "int"
        ),

        "pool_manufacturer_number" => array(
            "id" => 20,
            "name" => totranslate("Number of pools built"),
            "type" => "int"
        ),

        "temp_agency_number" => array(
            "id" => 21,
            "name" => totranslate("Number of temporary worker  hired"),
            "type" => "int"
        ),

        "bis_number" => array(
            "id" => 22,
            "name" => totranslate("Number of bis opened"),
            "type" => "int"
        ),

        /*
        Examples:    
        
        
        "player_teststat1" => array(   "id"=> 10,
                                "name" => totranslate("player test stat 1"), 
                                "type" => "int" ),
                                
        "player_teststat2" => array(   "id"=> 11,
                                "name" => totranslate("player test stat 2"), 
                                "type" => "float" )

*/
    )

);
