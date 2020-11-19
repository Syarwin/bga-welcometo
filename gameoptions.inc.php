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
 */

require_once('modules/php/constants.inc.php');

$game_options = [
  OPTION_ADVANCED => [
    'name' => totranslate('Advanced variant'),
    'values' => [
      OFF => [
        'name' => totranslate('No')
      ],
      ON => [
        'name' => totranslate('Yes'),
        'tmdisplay' => totranslate('Using advanced variant'),
        'nobeginner' => true
      ]
    ]
  ],

  OPTION_EXPERT => [
    'name' => totranslate('Expert rules'),
    'values' => [
      OFF => [
        'name' => totranslate('No')
      ],
      ON => [
        'name' => totranslate('Yes'),
        'tmdisplay' => totranslate('Using expert rules'),
        'nobeginner' => true
      ]
    ],
    'startcondition' => [
      ON => [
        [
          'type' => 'minplayers',
          'value' => 2,
          'message' => totranslate('The expert rules requires at least two player.')
        ]
      ],
    ]
  ]
];

$game_preferences = [
  AUTOMATIC => [
    'name' => totranslate('Automatic pool and park actions'),
    'needReload' => false,
    'values' => [
      DISABLED  => ['name' => totranslate('Disabled')],
      ENABLED   => ['name' => totranslate('Enabled')],
    ]
  ],
];
