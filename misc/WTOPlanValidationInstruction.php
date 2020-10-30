<?php

require_once('WTOPlans.php');
require_once('WTOUpperSheet.php');
require_once('WTOLowerSheet.php');

class WTOPlanValidationInstruction extends APP_GameClass
{
    function __construct($playerId, $planId, $houseEstates, $score)
    {
        $this->playerId = $playerId;
        $this->planId = $planId;
        $this->houseEstates = $houseEstates;
        $this->score = $score;
    }

    public function checkInstructionIsValid()
    {
        $plans = new WTOPlans();
        // TBD : Ensure project Ok
        $currentPlan = $plans->getPlan($this->planId);
        if (is_null($currentPlan))
            throw new BgaUserException(sprintf(_("This project is not available.")));
        // Ensure housingEstates allows to validate
        $checkResult = $plans->checkPlan(WTOUpperSheet::getPlayerSheet($this->playerId), $this->planId, $this->houseEstates);
        if (!$checkResult['validated'])
            throw new BgaUserException(sprintf(_("You cannot validate the project with those houses.")));
        if ($checkResult['playerHasChoiceToMake'])
            throw new BgaUserException(sprintf(_("You have selected too much estates for this plan, you have to choose.")));
        return true;
    }

    public function saveInstruction()
    {
        $plans = new WTOPlans();
        $score = $plans->getPlanScore($this->planId);

        $houseEstatesString = "'" . implode(",", $this->houseEstates) . "'";

        self::DbQuery("INSERT INTO plan_instruction (`player_id`, `plan_id`, `house_estates`, `score`)
        VALUES({$this->playerId}, {$this->planId}, {$houseEstatesString}, {$score})");
    }

    public function applyInstruction()
    {
        $plans = new WTOPlans();
        $plan = $plans->getPlan($this->planId);
        $planNumber = $plan['stackNumber'];
        WTOLowerSheet::updateProjectScore($this->playerId, $planNumber, $this->score);
        $housesUsed = [];
        if ($plans->isRegularPlan($this->planId))
            $housesUsed = WTOUpperSheet::setHousesEstatesUsedInPlan($this->playerId, $this->houseEstates);
        else {
            $housesUsed = $plans->getHousesUsedInAdvancedPlan($this->planId, $this->playerId);
            WTOUpperSheet::setHousesUsedInAdvancedPlan($this->playerId, $housesUsed);
        }
        $plans->validatePlan($this->planId);
        return ["planDone", clienttranslate('Player ${player_name} has realized plan number ${plan_number}, scoring ${score} points.'), array(
            'player_id' => $this->playerId,
            'plan_number' => $planNumber,
            'plan_id' => $this->planId,
            'score' => $this->score,
            'houses_used' => $housesUsed,
        )];
    }


    public static function loadInstructionsFromDB($playerId = null): array
    {
        $sql = "SELECT * FROM plan_instruction";
        if (!is_null($playerId))
            $sql .= " WHERE player_id={$playerId}";
        return array_map(function ($dbLine) {
            $houseEstates = ($dbLine['house_estates']) == '' ? [] : explode(",", $dbLine['house_estates']);
            return new self($dbLine['player_id'], $dbLine['plan_id'], $houseEstates, $dbLine['score']);
        }, self::getObjectListFromDB($sql));
    }

    public static function cleanPlanValidationInstructions()
    {
        self::DbQuery("TRUNCATE plan_instruction");
    }

    public static function canPlayerSubmitAnotherProject($playerId): bool
    {
        $savedInstructions = self::loadInstructionsFromDB($playerId);
        $filteredOutEstates = array_reduce($savedInstructions, function ($filter, $instruction) {
            return array_merge($filter, $instruction->houseEstates);
        }, []);

        $plans = new WTOPlans();
        $previouslyDonePlans = WTOLowerSheet::getAllPlayerSheets()[$playerId]->getAlreadyDonePlans();
        $alreadyRegisteredPlans = array_reduce($savedInstructions, function ($alreadyRegisteredPlans, $instruction) use ($plans) {
            $alreadyRegisteredPlans[] = $plans->getPlan($instruction->planId)['stackNumber'];
            return $alreadyRegisteredPlans;
        }, []);
        $alreadyDonePlans = array_merge($previouslyDonePlans, $alreadyRegisteredPlans);

        $plansStatus = $plans->checkPlans(WTOUpperSheet::getPlayerSheet($playerId), $alreadyDonePlans, $filteredOutEstates);
        $canStillBeValidatedPlansNumber = array_reduce($plansStatus, function ($count, $plan) {
            if ($plan['validated'])
                return $count + 1;
            return $count;
        }, 0);
        self::debug("canStillBeValidatedPlansNumber for {$playerId} : {$canStillBeValidatedPlansNumber}");
        return $canStillBeValidatedPlansNumber > 0;
    }
}
