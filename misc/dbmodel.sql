-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- welcometo implementation : © Geoffrey VOYER <geoffrey.voyer@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----
CREATE TABLE IF NOT EXISTS `plan_card` (
    `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `card_type` varchar(16) NOT NULL COMMENT "Represents the card id",
    `card_type_arg` int(11) NOT NULL COMMENT "Represents the plan number.",
    `card_location` varchar(16) NOT NULL COMMENT "Either in a stack (1, 2 or 3) or on the table. ",
    `card_location_arg` int(11) NOT NULL COMMENT "For the table : 1 when the plan has been approved, 0 otherwise",
    PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `construction_card` (
    `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `card_type` varchar(16) NOT NULL,
    `card_type_arg` int(11) NOT NULL,
    `card_location` varchar(16) NOT NULL,
    `card_location_arg` int(11) NOT NULL,
    PRIMARY KEY (`card_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `turn_instruction` (
    `player_id` int(10) unsigned NOT NULL,
    `stack_number` int(2) NOT NULL,
    `stack_action` int(2) NOT NULL,
    `house_id` int(11) NOT NULL,
    `roundabout` int(11),
    `permit_refusal` BOOLEAN,
    `action_name` ENUM(
        'none',
        'Bis',
        'Landscaper',
        'Real Estate Agent',
        'Surveyor',
        'Pool Manufacturer',
        'Temp Agency'
    ) NOT NULL,
    `new_fence` int(11),
    `estate_size_upgrade` int(11),
    `delta` int(11),
    `bis_house_id` int(11),
    `bis_copy_from` ENUM('left', 'right'),
    PRIMARY KEY (`player_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `houses` (
    `player_id` int(10) unsigned NOT NULL,
    `house_id` int(10) unsigned NOT NULL,
    `number` int(10) unsigned,
    `pool_built` BOOLEAN,
    `estate_fence_on_right` BOOLEAN,
    `used_in_plan` BOOLEAN,
    `is_bis` BOOLEAN,
    PRIMARY KEY (`player_id`, `house_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `plan_instruction` (
    `player_id` int(10) unsigned NOT NULL,
    `plan_id` int(10) NOT NULL,
    `house_estates` varchar(256) NOT NULL,
    `score` int(11) NOT NULL,
    PRIMARY KEY (`player_id`, `plan_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;

CREATE TABLE IF NOT EXISTS `lower_sheet` (
    `player_id` int(10) unsigned NOT NULL,
    `project_1_score` int(10) NOT NULL,
    `project_2_score` int(11) NOT NULL,
    `project_3_score` int(11) NOT NULL,
    `park_0_built` int(11) NOT NULL,
    `park_1_built` int(11) NOT NULL,
    `park_2_built` int(11) NOT NULL,
    `pools_built` int(11) NOT NULL,
    `temps_hired` int(11) NOT NULL,
    `real_estate_1` int(11) NOT NULL,
    `real_estate_2` int(11) NOT NULL,
    `real_estate_3` int(11) NOT NULL,
    `real_estate_4` int(11) NOT NULL,
    `real_estate_5` int(11) NOT NULL,
    `real_estate_6` int(11) NOT NULL,
    `bis_used` int(11) NOT NULL,
    `roundabout_used` int(11) NOT NULL,
    `permit_refusal` int(11) NOT NULL,
    PRIMARY KEY (`player_id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8 AUTO_INCREMENT = 1;