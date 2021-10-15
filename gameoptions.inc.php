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

namespace WTO;

require_once 'modules/php/constants.inc.php';

$game_options = [
  OPTION_ADVANCED => [
    'name' => totranslate('Advanced variant'),
    'values' => [
      OFF => [
        'name' => totranslate('No'),
      ],
      ON => [
        'name' => totranslate('Yes'),
        'tmdisplay' => totranslate('Advanced variant'),
        'description' => totranslate('Additional City Plans and Roundabouts'),
        'nobeginner' => true,
      ],
    ],
  ],

  OPTION_EXPERT => [
    'name' => totranslate('Expert rules'),
    'values' => [
      OFF => [
        'name' => totranslate('No'),
      ],
      ON => [
        'name' => totranslate('Yes'),
        'tmdisplay' => totranslate('Expert rules'),
        'description' => totranslate(
          'Choose one card for the house number, one card for the effect, and pass the remaining card to the next player'
        ),
        'nobeginner' => true,
      ],
    ],
    'startcondition' => [
      ON => [
        [
          'type' => 'minplayers',
          'value' => 2,
          'message' => totranslate('The expert rules requires at least two player.'),
        ],
      ],
    ],
  ],

  OPTION_BOARD => [
    'name' => totranslate('Board/expansion'),
    'values' => [
      OPTION_BOARD_BASE => [
        'name' => totranslate('Base game'),
      ],
      OPTION_BOARD_ICE_CREAM => [
        'name' => totranslate('Coming soon....'),
      ],
      /*
      OPTION_BOARD_ICE_CREAM => [
        'name' => totranslate('Ice Cream Truck'),
        'tmdisplay' => totranslate('Ice Cream'),
        'nobeginner' => true,
      ],
      */
    ],
    'startcondition' => [
      OPTION_BOARD_ICE_CREAM => [
        [
          'type' => 'minplayers',
          'value' => 60,
          'message' => totranslate('Coming soon....'),
        ],
      ],
    ],
  ],
];

$game_preferences = [
  AUTOMATIC => [
    'name' => totranslate('Automatic pool and park actions'),
    'needReload' => false,
    'values' => [
      DISABLED => ['name' => totranslate('Disabled')],
      ENABLED => ['name' => totranslate('Enabled')],
    ],
  ],

  CONFIRM => [
    'name' => totranslate('Turn confirmation'),
    'needReload' => false,
    'values' => [
      CONFIRM_TIMER => ['name' => totranslate('Enabled with timer')],
      CONFIRM_ENABLED => ['name' => totranslate('Enabled')],
      CONFIRM_DISABLED => ['name' => totranslate('Disabled')],
    ],
  ],

  END_OF_TURN_ANIMATION => [
    'name' => totranslate('End of turn animation'),
    'needReload' => false,
    'values' => [
      ENABLED => ['name' => totranslate('Enabled')],
      DISABLED => ['name' => totranslate('Disabled')],
    ],
  ],
];
