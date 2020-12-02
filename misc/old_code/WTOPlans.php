<?php

require_once('WTOUpperSheet.php');
require_once('WTOLowerSheet.php');

class WTOPlans extends APP_GameClass
{
    function __construct()
    {
        // TBD : Singleton useful?
        $this->projectCards = self::getNew("module.common.deck");
        $this->projectCards->init("plan_card");
        $this->plansScores = [
            "! Cards Id shall start from one !",
            array("first" => 8, "later" => 4),
            array("first" => 8, "later" => 4),
            array("first" => 8, "later" => 4),
            array("first" => 6, "later" => 3),
            array("first" => 8, "later" => 4),
            array("first" => 10, "later" => 6),

            array("first" => 11, "later" => 6),
            array("first" => 10, "later" => 6),
            array("first" => 12, "later" => 7),
            array("first" => 8, "later" => 4),
            array("first" => 9, "later" => 5),
            array("first" => 9, "later" => 5),

            array("first" => 12, "later" => 7),
            array("first" => 13, "later" => 7),
            array("first" => 7, "later" => 3),
            array("first" => 7, "later" => 3),
            array("first" => 11, "later" => 6),
            array("first" => 13, "later" => 7),

            array("first" => 7, "later" => 4),
            array("first" => 8, "later" => 4),
            array("first" => 10, "later" => 5),
            array("first" => 6, "later" => 3),
            array("first" => 8, "later" => 3),
            array("first" => 7, "later" => 4),
            array("first" => 6, "later" => 3),
            array("first" => 10, "later" => 5),
            array("first" => 7, "later" => 4),
            array("first" => 8, "later" => 3)
        ];
    }

    public function setupPlans($advanced)
    {
        // Create cards templates for each plan number.
        $projectCards = array(1 => array(), 2 => array(), 3 => array());
        for ($value = 1; $value <= 6; $value++) {
            $projectCards[1][] = array('type' => $value, 'type_arg' => 1, 'nbr' => 1);
            $projectCards[2][] = array('type' => $value + 6, 'type_arg' => 2, 'nbr' => 1);
            $projectCards[3][] = array('type' => $value + 12, 'type_arg' => 3, 'nbr' => 1);
        }

        // Note for debuggers : Compared to png card names given by the editor, 
        // the Carte_Plan_*_19 is missing, so there is a -1 difference (Carte_20 has the id 19).
        if ($advanced) {
            $projectCards[1][] = array('type' => 20, 'type_arg' => 1, 'nbr' => 1);
            $projectCards[1][] = array('type' => 22, 'type_arg' => 1, 'nbr' => 1);
            $projectCards[1][] = array('type' => 23, 'type_arg' => 1, 'nbr' => 1);
            $projectCards[1][] = array('type' => 25, 'type_arg' => 1, 'nbr' => 1);
            $projectCards[1][] = array('type' => 27, 'type_arg' => 1, 'nbr' => 1);
            $projectCards[2][] = array('type' => 19, 'type_arg' => 2, 'nbr' => 1);
            $projectCards[2][] = array('type' => 21, 'type_arg' => 2, 'nbr' => 1);
            $projectCards[2][] = array('type' => 24, 'type_arg' => 2, 'nbr' => 1);
            $projectCards[2][] = array('type' => 26, 'type_arg' => 2, 'nbr' => 1);
            $projectCards[2][] = array('type' => 28, 'type_arg' => 2, 'nbr' => 1);
        }

        // Create cards an pick one from each color.
        foreach ($projectCards as $planNumber => $cards) {
            $this->projectCards->createCards($cards, $planNumber);
            $this->projectCards->shuffle($planNumber);
            $this->projectCards->pickCardForLocation($planNumber, 'table');
        }
    }

    public function getPlans(): array
    {
        $plans = $this->projectCards->getCardsInLocation('table');
        return array_values(array_map(function ($plan) {
            return array('id' => $plan['type'], 'approved' => boolval($plan['location_arg']), 'stackNumber' => $plan['type_arg']);
        }, $plans));
    }

    public function getPlan($planId)
    {
        $matchingPlans = array_values(array_filter($this->getPlans(), function ($plan) use ($planId) {
            return $plan['id'] == $planId;
        }));
        if (count($matchingPlans) == 0)
            return null;
        return $matchingPlans[0];
    }

    public function getPlanScore($planId)
    {
        $plan = $this->getPlan($planId);
        if ($plan['approved'])
            return $this->plansScores[$plan['id']]["later"];
        return $this->plansScores[$plan['id']]["first"];
    }

    public function checkPlans($upperSheet, $planNumberDone = array(), $filterOut = array())
    {
        self::dump("Calling checkPlans", $planNumberDone);
        $plans = $this->getPlans();
        $plansStatus = [];
        foreach ($plans as $key => $plan) {
            if (in_array($plan['stackNumber'], $planNumberDone))
                $plansStatus[$plan['id']] = array('validated' => false);
            else
                $plansStatus[$plan['id']] = $this->checkPlan($upperSheet, $plan['id'], null, $filterOut);
        }
        return $plansStatus;
    }

    public function checkPlan($upperSheet, $planId, $choiceFilterIn = null, $filterOut = array())
    {
        $unusedHousingEstates = $upperSheet->getUnusedHousingEstatesNumberPerSize($choiceFilterIn, $filterOut);
        switch ($planId) {
            case 1:
                return $this->checkRegularPlan($unusedHousingEstates, array(1 => 6));
            case 2:
                return $this->checkRegularPlan($unusedHousingEstates, array(2 => 4));
            case 3:
                return $this->checkRegularPlan($unusedHousingEstates, array(3 => 3));
            case 4:
                return $this->checkRegularPlan($unusedHousingEstates, array(4 => 2));
            case 5:
                return $this->checkRegularPlan($unusedHousingEstates, array(5 => 2));
            case 6:
                return $this->checkRegularPlan($unusedHousingEstates, array(6 => 2));
            case 7:
                return $this->checkRegularPlan($unusedHousingEstates, array(1 => 3, 6 => 1));
            case 8:
                return $this->checkRegularPlan($unusedHousingEstates, array(2 => 2, 5 => 1));
            case 9:
                return $this->checkRegularPlan($unusedHousingEstates, array(3 => 2, 4 => 1));
            case 10:
                return $this->checkRegularPlan($unusedHousingEstates, array(3 => 1, 6 => 1));
            case 11:
                return $this->checkRegularPlan($unusedHousingEstates, array(4 => 1, 5 => 1));
            case 12:
                return $this->checkRegularPlan($unusedHousingEstates, array(1 => 3, 4 => 1));
            case 13:
                return $this->checkRegularPlan($unusedHousingEstates, array(1 => 1, 2 => 1, 6 => 1));
            case 14:
                return $this->checkRegularPlan($unusedHousingEstates, array(1 => 1, 4 => 1, 5 => 1));
            case 15:
                return $this->checkRegularPlan($unusedHousingEstates, array(3 => 1, 4 => 1));
            case 16:
                return $this->checkRegularPlan($unusedHousingEstates, array(2 => 1, 5 => 1));
            case 17:
                return $this->checkRegularPlan($unusedHousingEstates, array(1 => 1, 2 => 2, 3 => 1));
            case 18:
                return $this->checkRegularPlan($unusedHousingEstates, array(2 => 1, 3 => 1, 5 => 1));
            case 19:
                $lowerSheet = WTOLowerSheet::getAllPlayerSheets()[$upperSheet->playerId];
                $streets = [0, 1, 2];
                $matchingStreets = array_filter($streets, function ($street) use ($lowerSheet) {
                    return $lowerSheet->hasAllParkBuilt($street);
                });
                return array('validated' => count($matchingStreets) >= 2, 'playerHasChoiceToMake' => false);
            case 20:
                return array('validated' => $upperSheet->hasBuiltAndNotUsedHouses($this->getHousesUsedInAdvancedPlan($planId)), 'playerHasChoiceToMake' => false);
            case 21:
                $lowerSheet = WTOLowerSheet::getAllPlayerSheets()[$upperSheet->playerId];
                $streets = [0, 1, 2];
                $matchingStreets = array_filter($streets, function ($street) use ($lowerSheet, $upperSheet) {
                    return $lowerSheet->hasAllParkBuilt($street) && $upperSheet->hasAllPoolsBuilt($street) && $upperSheet->hasARoundabout($street);
                });
                return array('validated' => count($matchingStreets) >= 1, 'playerHasChoiceToMake' => false);
            case 22:
                return array('validated' => $upperSheet->hasBuiltAndNotUsedHouses($this->getHousesUsedInAdvancedPlan($planId)), 'playerHasChoiceToMake' => false);
            case 23:
                $numberPerStreets = $upperSheet->getBisNumberPerStreet();
                $matchingStreets = array_filter($numberPerStreets, function ($number) {
                    return $number >= 5;
                });
                return array('validated' => (count($matchingStreets) > 0), 'playerHasChoiceToMake' => false);
            case 24:
                $streets = [0, 1, 2];
                $matchingStreets = array_filter($streets, function ($street) use ($upperSheet) {
                    return $upperSheet->hasAllPoolsBuilt($street);
                });
                return array('validated' => count($matchingStreets) >= 2, 'playerHasChoiceToMake' => false);
            case 25:
                $lowerSheet = WTOLowerSheet::getAllPlayerSheets()[$upperSheet->playerId];
                return array('validated' => $lowerSheet->tempsHired >= 7, 'playerHasChoiceToMake' => false);
            case 26:
                $lowerSheet = WTOLowerSheet::getAllPlayerSheets()[$upperSheet->playerId];
                $street = 2;
                return array('validated' => $lowerSheet->hasAllParkBuilt($street) && $upperSheet->hasAllPoolsBuilt($street), 'playerHasChoiceToMake' => false);
            case 27:
                return array('validated' => $upperSheet->hasBuiltAndNotUsedHouses($this->getHousesUsedInAdvancedPlan($planId)), 'playerHasChoiceToMake' => false);
            case 28:
                $lowerSheet = WTOLowerSheet::getAllPlayerSheets()[$upperSheet->playerId];
                $street = 1;
                return array('validated' => $lowerSheet->hasAllParkBuilt($street) && $upperSheet->hasAllPoolsBuilt($street), 'playerHasChoiceToMake' => false);
        }
    }

    private function checkRegularPlan($unusedHousingEstates, $requiredHousingEstatesSizes)
    {
        $choice = false;
        foreach ($requiredHousingEstatesSizes as $size => $requiredHousingEstates) {
            if ($unusedHousingEstates[$size] < $requiredHousingEstates)
                return array('validated' => false);
            if ($unusedHousingEstates[$size] > $requiredHousingEstates)
                $choice = true;
        }
        return array('validated' => true, 'playerHasChoiceToMake' => $choice);
    }

    public function validatePlan($planId)
    {
        $planCard = array_values($this->projectCards->getCardsOfType($planId))[0];
        if ($planCard['location_arg'] == 0) {
            $this->projectCards->moveCard($planCard['id'], 'table', 1);
        }
    }

    public function validateAllPlans()
    {
        $this->projectCards->moveAllCardsInLocation('table', 'table', 0, 1);
    }

    public function isRegularPlan($planId)
    {
        return $planId <= 18;
    }

    public function getHousesUsedInAdvancedPlan($planId, $playerId = null)
    {
        switch ($planId) {
            case 19:
                return [];
            case 20:
                return range(20, 32);
            case 21:
                return [];
            case 22:
                return range(0, 9);
            case 23:
                $upperSheet = WTOUpperSheet::getPlayerSheet($playerId);
                $streetWithFiveBis = array_search(5, $upperSheet->getBisNumberPerStreet(), true);
                return $upperSheet->getBisHouses($streetWithFiveBis);
            case 24:
                return [];
            case 25:
                return [];
            case 26:
                return [];
            case 27:
                return [0, 9, 10, 20, 21, 32];
            case 28:
                return [];
            default:
                throw new BgaUserException(sprintf(_("Not yet implemented.")));
        }
    }
}
