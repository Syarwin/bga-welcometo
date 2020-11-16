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

  public function permitRefusal()
  {
    self::setAjaxMode();
    $this->game->permitRefusal();
    self::ajaxResponse();
  }


  // Standard mode => one stack
  public function chooseStack()
  {
    self::setAjaxMode();
    $stack = self::getArg("stack", AT_posint, true);
    $this->game->chooseCards($stack);
    self::ajaxResponse();
  }

  // Non-Standard mode => two stacks
  public function chooseStacks()
  {
    self::setAjaxMode();
    $number = self::getArg("number", AT_posint, true);
    $action = self::getArg("action", AT_posint, true);
    $this->game->chooseCards([$number, $action]);
    self::ajaxResponse();
  }


  public function writeNumber()
  {
    self::setAjaxMode();
    $number = self::getArg("number", AT_posint, true);
    $x = self::getArg("x", AT_posint, true);
    $y = self::getArg("y", AT_posint, true);
    $this->game->writeNumber($number, [$x, $y]);
    self::ajaxResponse();
  }


  /////////////////////////////
  /// Non-automatic actions ///
  /////////////////////////////

  public function passAction()
  {
    self::setAjaxMode();
    $this->game->passAction();
    self::ajaxResponse();
  }

  // Generic handler
  public function scribbleZone()
  {
    self::setAjaxMode();
    $zone = [ (int) self::getArg("x", AT_posint, true) ];
    if(self::isArg("y"))
      $zone[] = (int) self::getArg("y", AT_posint, true);
    $this->game->scribbleZone($zone);
    self::ajaxResponse();
  }


  public function writeNumberBis()
  {
    self::setAjaxMode();
    $number = self::getArg("number", AT_posint, true);
    $x = self::getArg("x", AT_posint, true);
    $y = self::getArg("y", AT_posint, true);
    $this->game->writeNumberBis($number, [$x, $y]);
    self::ajaxResponse();
  }


  /////////////////////////////
  //// Confirm / pass turn ////
  /////////////////////////////
  public function cancelTurn()
  {
    self::setAjaxMode();
    $this->game->cancelTurn();
    self::ajaxResponse();
  }

  public function confirmTurn()
  {
    self::setAjaxMode();
    $this->game->confirmTurn();
    self::ajaxResponse();
  }


  /////////////////////////////
  ///// Choose card plan  /////
  /////////////////////////////
  public function choosePlan()
  {
    self::setAjaxMode();
    $planId = self::getArg("plan", AT_posint, true);
    $this->game->choosePlan($planId);
    self::ajaxResponse();
  }

  public function validatePlan(){
    self::setAjaxMode();
    $arg = self::getArg("planArg", AT_json, true);
    $this->validateJSonAlphaNum($arg, "planArg");
    $this->game->validatePlan($arg);
    self::ajaxResponse();
  }




  //////////////////
  ///// UTILS  /////
  //////////////////
  public function validateJSonAlphaNum($value, $argName = "unknown"){
      if (is_array($value)) {
          foreach ($value as $key => $v) {
              $this->validateJSonAlphaNum($key);
              $this->validateJSonAlphaNum($v);
          }
          return true;
      }
      if (is_int($value)) return true;
      $bValid = ( preg_match( "/^[0-9a-zA-Z- ]*$/", $value ) === 1 );
      if (!$bValid)
          throw new feException( "Bad value for: $argName", true, true, FEX_bad_input_argument );
      return true;
  }

}
