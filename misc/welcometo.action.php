<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * welcometo implementation : © Geoffrey VOYER <geoffrey.voyer@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on https://boardgamearena.com.
 * See http://en.doc.boardgamearena.com/Studio for more information.
 * -----
 * 
 * welcometo.action.php
 *
 * welcometo main action entry point
 *
 *
 * In this file, you are describing all the methods that can be called from your
 * user interface logic (javascript).
 *       
 * If you define a method "myAction" here, then you can call it from your javascript code with:
 * this.ajaxcall( "/welcometo/welcometo/myAction.html", ...)
 *
 */
require_once('modules/WTOTurnInstruction.php');

class action_welcometo extends APP_GameAction
{
  // Constructor: please do not modify
  public function __default()
  {
    if (self::isArg('notifwindow')) {
      $this->view = "common_notifwindow";
      $this->viewArgs['table'] = self::getArg("table", AT_posint, true);
    } else {
      $this->view = "welcometo_welcometo";
      self::trace("Complete reinitialization of board game");
    }
  }

  public function registerPlayerTurn()
  {
    self::setAjaxMode();

    $stackNumber = self::getArg("stack_number", AT_enum, true, NULL, array(0, 1, 2));
    $stackAction = self::getArg("stack_action", AT_enum, true, NULL, array(0, 1, 2));
    $house = self::getArg("house_id", AT_enum, true, NULL, range(0, 32));
    $roundabout = self::getArg("roundabout", AT_enum, false, NULL, range(0, 32));
    $permitRefusal = self::getArg("permit_refusal", AT_bool, false, false);
    $useAction = self::getArg("use_action", AT_bool, true, NULL);

    // TBD : Stuff specific to actions.
    $possibleEstateFence = array_merge(range(0, 8), range(10, 19), range(21, 31));
    $newFence = self::getArg("fence_id", AT_enum, false, NULL, $possibleEstateFence);
    $estateSizeUpgrade = self::getArg("estate_size", AT_enum, false, NULL, range(1, 6));
    $delta = self::getArg("delta", AT_enum, false, 0, [-2, -1, 0, 1, 2]);
    $bisHouse = self::getArg("bis_house_id", AT_enum, false, NULL, range(0, 32));
    $bisCopyFrom = self::getArg("bis_copy_from", AT_enum, false, NULL, ['left', 'right']);


    $action = array('useAction' => $useAction, 'newFence' => $newFence, 'estateSizeUpgrade' => $estateSizeUpgrade, 'delta' => $delta, 'bisHouseId' => $bisHouse, 'bisCopyFrom' => $bisCopyFrom);

    $this->game->registerPlayerTurn($stackNumber, $stackAction, $house, $roundabout, $permitRefusal, $action);

    self::ajaxResponse();
  }


  public function registerPlanValidation()
  {
    self::setAjaxMode();

    $projectId = self::getArg("project_id", AT_enum, true, NULL, range(0, 28));
    $houseEstatesRaw = self::getArg("house_estates", AT_numberlist, '', false);
    if ($houseEstatesRaw == '')
      $houseEstates = array();
    else
      $houseEstates = explode(',', $houseEstatesRaw);

    $this->game->registerPlanValidation($projectId, $houseEstates);

    self::ajaxResponse();
  }
}
