<?php
namespace WTO\States;

use \WTO\Game\Players;
use \WTO\Game\Globals;
use \WTO\Game\Log;
use \WTO\Game\StateMachine;
use \WTO\Game\UserException;
use \WTO\Helpers\QueryBuilder;

/*
 * Handle the plans validation
 */
trait PlanValidationTrait
{
  //////////////////////////////
  //////// CHOOSE CARD /////////
  //////////////////////////////
  /*
   * Skip the state if no plan can be scored
   */
  function stChoosePlan($player)
  {
    $plans = $player->getScorablePlans();
    if(empty($plans)){
      StateMachine::nextState("none");
      return true; // Skip this state
    }
  }

  /*
   * Return the list of scorable plans
   */
  function argChoosePlan($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $data['selectablePlans'] = $player->getScorablePlans();
    return $data;
  }

  function choosePlan($planId)
  {
    // Sanity checks
    StateMachine::checkAction("choosePlan");
    $player = Players::getCurrent();
    $args = self::argChoosePlan($player);
    if(!in_array($planId, $args['selectablePlans']))
      throw new UserException(totranslate("You cannot select this plan to validate"));

    // Do the action (logging the choice for rest of the turn)
    $player->choosePlan($planId);

    // Move on to next state
    StateMachine::nextState("validatePlan");
  }


  //////////////////////////////////
  //////// PLAN VALIDATION /////////
  //////////////////////////////////
  function stValidatePlan($player)
  {
    $plan = $player->getCurrentPlan();
    if($plan->isAutomatic()){
      $this->validatePlan([]);
      return true; // Skip this state
    }
  }

  /*
   * Return data needed to compute actions asked by the plan
   */
  function argValidatePlan($player)
  {
    $data = $this->argPrivatePlayerTurn($player);
    $plan = $player->getCurrentPlan();
    $data['currentPlan'] = $plan->argValidate($player);
    return $data;
  }


  function validatePlan($arg)
  {
    // Sanity checks
    StateMachine::checkAction("validatePlan");
    $player = Players::getCurrent();
    $plan = $player->getCurrentPlan();
    if(!$plan->canBeScored($player))
      throw new UserException(totranslate("Conditions are not fullfiled"));

    $plan->validate($player, $arg);
    $player->updateScores();

    // Move on to next state
    StateMachine::nextState("reshuffle");
  }



  /////////////////////////////
  //////// RESHUFFLE  /////////
  /////////////////////////////
  function stAskReshuffle($player)
  {
    $query = new QueryBuilder('plan_validation');
    $previous = $query->where('turn', '<', Globals::getCurrentTurn())->count();
    if($previous > 0 || Globals::isSolo()){
      StateMachine::nextState("reshuffle");
      return true;
    }
  }


  function reshuffle()
  {
    StateMachine::checkAction("reshuffle");
    $player = Players::getCurrent();
    $plan = $player->getCurrentPlan();
    $plan->askForReshuffle($player);

    // Move on to next state
    StateMachine::nextState("reshuffle");
  }

}
