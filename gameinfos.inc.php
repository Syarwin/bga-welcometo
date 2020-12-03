<?php
/*
    From this file, you can edit the various meta-information of your game.

    Once you modified the file, don't forget to click on "Reload game informations" from the Control Panel in order in can be taken into account.

    See documentation about this file here:
    http://en.doc.boardgamearena.com/Game_meta-information:_gameinfos.inc.php

*/

$gameinfos = [
  'game_name' => "Welcome To",
  'designer' => 'Benoit Turpin',
  'artist' => 'Anne Heidsieck',
  'year' => 2018,
  'publisher' => 'Blue Cocker Games',
  'publisher_website' => 'http://www.bluecocker.com/',
  'publisher_bgg_id' => 26604,
  'bgg_id' => 233867,


  'players' => [1,2,3,4,5,6,7,8,9,10,11,12],
  'suggest_player_number' => 4,
  'not_recommend_player_number' => null,

  'estimated_duration' => 35,
  'fast_additional_time' => 30,
  'medium_additional_time' => 40,
  'slow_additional_time' => 50,

  'tie_breaker_description' => totranslate("In case of a draw, the player with the most completed estates wins. In case of another draw, the one with the most size 1 estates wins, then size 2, etc."),
  'losers_not_ranked' => false,

  'is_beta' => 1,
  'is_coop' => 0,

  'complexity' => 2,
  'luck' => 1,
  'strategy' => 4,
  'diplomacy' => 1,

  'player_colors' => ["ff0000", "008000", "0000ff", "ffa500", "773300"],
  'favorite_colors_support' => true,
  'disable_player_order_swap_on_rematch' => false,

  'game_interface_width' => [
    'min' => 740,
    'max' => null
  ],

  'presentation' => [
    totranslate("As an American architect in the 50s, during the Baby Boom, you are tasked to create the nicest housing estates, with luxurious parks and fancy pools."),
    totranslate("But beware of the competition! Will you be able to become the greatest architect?")
  ],

  'tags' => [2, 11, 106],


//////// BGA SANDBOX ONLY PARAMETERS (DO NOT MODIFY)

// simple : A plays, B plays, C plays, A plays, B plays, ...
// circuit : A plays and choose the next player C, C plays and choose the next player D, ...
// complex : A+B+C plays and says that the next player is A+B
  'is_sandbox' => false,
  'turnControl' => 'complex'
////////
];
