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
 * gameoptions.inc.php
 *
 * welcometo game options description
 * 
 * In this file, you can define your game options (= game variants).
 *   
 * Note: If your game has no variant, you don't have to modify this file.
 *
 * Note²: All options defined in this file should have a corresponding "game state labels"
 *        with the same ID (see "initGameStateLabels" in welcometo.game.php)
 *
 * !! It is not a good idea to modify this file when a game is running !!
 *
 */

$game_options = array(

    // note: game variant ID should start at 100 (ie: 100, 101, 102, ...). The maximum is 199.
    100 => array(
        'name' => totranslate('Advanced variant'),
        'values' => array(

            // A simple value for this option:
            0 => array('name' => totranslate('No')),

            // A simple value for this option.
            // If this value is chosen, the value of "tmdisplay" is displayed in the game lobby
            1 => array('name' => totranslate('Yes'), 'tmdisplay' => totranslate('Using advanced variant'), 'nobeginner' => true)
        )
    ),

    101 => array(
        'name' => totranslate('Expert rules'),
        'values' => array(

            // A simple value for this option:
            0 => array('name' => totranslate('No')),

            // A simple value for this option.
            // If this value is chosen, the value of "tmdisplay" is displayed in the game lobby
            1 => array('name' => totranslate('Yes'), 'tmdisplay' => totranslate('Using expert rules'), 'nobeginner' => true)
        ),
        'startcondition' => array(
            1 => array(
                array(
                    'type' => 'minplayers',
                    'value' => 2,
                    'message' => totranslate('The expert rules requires at least two player.')
                )
            ),
        )
    )

);
