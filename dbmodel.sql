-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- welcometo implementation : © Geoffrey VOYER <geoffrey.voyer@gmail.com>
--
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

CREATE TABLE IF NOT EXISTS `log` (
  `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `turn` int(11) NOT NULL,
  `player_id` int(11) NOT NULL,
  `action` varchar(16) NOT NULL,
  `action_arg` json,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `construction_cards` (
  `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_location` varchar(16) NOT NULL,
  `card_state` int(11) NOT NULL,
  `number` int(11) NOT NULL,
  `action` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;


CREATE TABLE IF NOT EXISTS `plan_cards` (
  `card_id` int(10) unsigned NOT NULL,
  `card_location` varchar(16) NOT NULL,
  `card_state` int(11) NOT NULL,
  PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `plan_validation` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `card_id` int(10) unsigned NOT NULL,
  `player_id` int(10) NOT NULL,
  `turn` int(10) NOT NULL,
  `reshuffle` BOOLEAN DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;


CREATE TABLE IF NOT EXISTS `scribbles` (
  `scribble_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scribble_location` varchar(100) NOT NULL,
  `scribble_state` int(11) NOT NULL,
  `turn` int(10) NOT NULL,
  PRIMARY KEY (`scribble_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `houses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `player_id` int(10) NOT NULL,
  `x` int(10) NOT NULL,
  `y` int(10) NOT NULL,
  `number` int(10) NOT NULL,
  `is_bis` BOOLEAN,
  `turn` int(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;


ALTER TABLE `player` ADD `player_state` INT(10) UNSIGNED;

ALTER TABLE `gamelog` ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0;
