<?php

require_once('WTOHousingEstate.php');

class WTOLowerSheet extends APP_GameClass
{
    function __construct($playerId, $projectScores, $parksBuilt, $poolsBuilt, $tempsHired, $realEstate, $bisUsed, $roundaboutUsed, $permitRefusals)
    {
        $this->playerId = $playerId;
        $this->projectScores = $projectScores;
        $this->parksBuilt = $parksBuilt;
        $this->poolsBuilt = $poolsBuilt;
        $this->tempsHired = $tempsHired;
        $this->realEstate = $realEstate;
        $this->bisUsed = $bisUsed;
        $this->roundaboutUsed = $roundaboutUsed;
        $this->permitRefusals = $permitRefusals;
        $this->maxRealEstateUpgrades = array(1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 4, 6 => 4);
        $this->maxParkNumber = [3, 4, 5];
        $this->maxTempNumber = 11;
        $this->maxBisNumber = 9;
    }

    public static function setupLowerSheets($players)
    {
        $sql = "INSERT INTO lower_sheet (`player_id`, `project_1_score`, `project_2_score`, `project_3_score`, `park_0_built` ,`park_1_built` ,`park_2_built` ,`pools_built` ,`temps_hired` ,`real_estate_1` ,`real_estate_2` ,`real_estate_3` ,`real_estate_4` ,`real_estate_5` ,`real_estate_6` ,`bis_used` ,`roundabout_used` ,`permit_refusal`) VALUES ";
        $values = [];
        foreach ($players as $playerId => $player) {
            $values[] = "({$playerId}, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0)";
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
    }

    public static function getAllPlayerSheets(): array
    {
        $rawSheets = self::getCollectionFromDB("SELECT * FROM lower_sheet");
        $sheets = [];
        foreach ($rawSheets as $playerId => $rawSheet) {
            $planScores = array(1 => $rawSheet['project_1_score'], 2 => $rawSheet['project_2_score'], 3 => $rawSheet['project_3_score']);
            $parksBuilt = [$rawSheet['park_0_built'], $rawSheet['park_1_built'], $rawSheet['park_2_built']];
            $realEstate = array(1 => $rawSheet['real_estate_1'], 2 => $rawSheet['real_estate_2'], 3 => $rawSheet['real_estate_3'], 4 => $rawSheet['real_estate_4'], 5 => $rawSheet['real_estate_5'], 6 => $rawSheet['real_estate_6']);
            $sheets[$playerId] = new self($rawSheet['player_id'], $planScores, $parksBuilt, $rawSheet['pools_built'], $rawSheet['temps_hired'], $realEstate, $rawSheet['bis_used'], $rawSheet['roundabout_used'], $rawSheet['permit_refusal']);
        }
        return $sheets;
    }

    public static function updateProjectScore($playerId, $projectNumber, $score)
    {
        self::DbQuery("UPDATE lower_sheet SET `project_{$projectNumber}_score`=$score WHERE player_id={$playerId}");
    }

    public function checkCanUpgradeRealEstate($size)
    {
        if ($this->realEstate[$size] >= $this->maxRealEstateUpgrades[$size]) {
            throw new BgaUserException(sprintf(_("This Real Estate cannot be promoted more, it has alreay reached its maximum value.")));
        }
        return true;
    }

    public function checkCanBuildPark($street)
    {
        if ($this->parksBuilt[$street] >= $this->maxParkNumber[$street]) {
            throw new BgaUserException(sprintf(_("You already have reached the maximum number of parks on this street.")));
        }
        return true;
    }

    public function hasAllParkBuilt($street)
    {
        return ($this->parksBuilt[$street] >= $this->maxParkNumber[$street]);
    }

    public function checkCanUseTemp()
    {
        if ($this->tempsHired >= $this->maxTempNumber) {
            throw new BgaUserException(sprintf(_("You already have reached the maximum number of temporary worker to hire.")));
        }
        return true;
    }

    public function checkCanUseBis()
    {
        if ($this->bisUsed >= $this->maxBisNumber) {
            throw new BgaUserException(sprintf(_("You already have reached the maximum number of Bis actions.")));
        }
        return true;
    }

    public function computeDetailedPlayerScore($upperSheet, $isSoloGame)
    {
        $scores = array(
            'plans' => $this->computeDetailedPlanScore(),
            'parks' => $this->computeDetailedParkScore(),
            'pools' => $this->computeDetailedPoolScore(),
            'temps' => $this->computeDetailedTempAgencyScore($isSoloGame),
            'bis' => $this->computeBisScore(),
            'penalties' => $this->computePenaltiesScore(),
        );
        $scores = array_merge($scores, $this->computeDetailedRealEstateScores($upperSheet));
        $total = 0;
        foreach ($scores as $category => $values) {
            if (in_array($category, ["bis", "penalties"]))
                $total -= $values['total'];
            else
                $total += $values['total'];
        }
        $scores['total'] = array('total' => $total);
        return $scores;
    }

    private function computeDetailedParkScore()
    {
        $parkBuiltScoreMapping = [[0, 2, 4, 10], [0, 2, 4, 6, 14], [0, 2, 4, 6, 8, 18]];
        $parkScores = [];
        for ($parkId = 0; $parkId < 3; $parkId++) {
            $parkScores["{$parkId}"] = $parkBuiltScoreMapping[$parkId][$this->parksBuilt[$parkId]];
        }
        $parkScores['total'] = array_sum($parkScores);
        return $parkScores;
    }

    private function computeDetailedPlanScore()
    {
        $planScores = $this->projectScores;
        $planScores['total'] = array_sum($planScores);
        return $planScores;
    }

    private function computeDetailedPoolScore()
    {
        $poolScores = [];
        $poolScoreMapping = [0, 3, 6, 9, 13, 17, 21, 26, 31, 36];
        $poolScores['total'] = $poolScoreMapping[$this->poolsBuilt];
        return $poolScores;
    }

    private function computeDetailedTempAgencyScore($isSoloGame)
    {
        if ($isSoloGame)
            return $this->tempsHired >= 6 ? array('total' => 7, 'rank' => 1) : array('total' => 0, 'rank' => "Solo game : Didn't build at least 6.");
        $ranking = self::computeTemporaryWorkersRankings();
        switch ($ranking[$this->playerId]) {
            case 1:
                return array('total' => 7, 'rank' => 1);
            case 2:
                return array('total' => 4, 'rank' => 2);
            case 3:
                return array('total' => 1, 'rank' => 3);
            default:
                return array('total' => 0, 'rank' => $ranking[$this->playerId]);
        }
    }

    private function computeDetailedRealEstateScores($upperSheet)
    {
        $realEstatesScores = [];
        $realEstatesScoreMapping = array(
            1 => [1, 3],
            2 => [2, 3, 4],
            3 => [3, 4, 5, 6],
            4 => [4, 5, 6, 7, 8],
            5 => [5, 6, 7, 8, 10],
            6 => [6, 7, 8, 10, 12]
        );

        $nbRealEstateBuilt = $upperSheet->getCompleteHousingEstatesPerSize();
        foreach ($this->realEstate as $size => $improvmentsDone) {
            $scoreMultiplier = $realEstatesScoreMapping[$size][$improvmentsDone];
            $realEstatesScores["real_estate_{$size}"] = array(
                'number' => $nbRealEstateBuilt[$size],
                'total' => $nbRealEstateBuilt[$size] * $scoreMultiplier
            );
        }
        return $realEstatesScores;
    }

    private function computeBisScore()
    {
        $bisScores = [];
        $bisScoreMapping = [0, 1, 3, 6, 9, 12, 16, 20, 24, 28];
        $bisScores['total'] = $bisScoreMapping[$this->bisUsed];
        return $bisScores;
    }

    private function computePenaltiesScore()
    {
        $penaltiesScores = [];
        $roundaboutScoreMapping = [0, 3, 8];
        $permitRefusalScoreMapping = [0, 0, 3, 5];
        $penaltiesScores['total'] = $permitRefusalScoreMapping[$this->permitRefusals] + $roundaboutScoreMapping[$this->roundaboutUsed];
        return $penaltiesScores;
    }

    public static function upgradeRealEstate($playerId, $estateSizeUpgrade)
    {
        self::DbQuery("UPDATE lower_sheet SET `real_estate_{$estateSizeUpgrade}`=`real_estate_{$estateSizeUpgrade}`+1 WHERE player_id={$playerId}");
    }


    public static function buildPark($playerId, $street)
    {
        self::DbQuery("UPDATE lower_sheet SET `park_{$street}_built`=`park_{$street}_built`+1 WHERE player_id={$playerId}");
    }

    public static function buildPool($playerId)
    {
        self::DbQuery("UPDATE lower_sheet SET `pools_built`=`pools_built`+1 WHERE player_id={$playerId}");
    }

    public static function hireTemporaryWorker($playerId)
    {
        self::DbQuery("UPDATE lower_sheet SET `temps_hired`=`temps_hired`+1 WHERE player_id={$playerId}");
    }

    public static function useBis($playerId)
    {
        self::DbQuery("UPDATE lower_sheet SET `bis_used`=`bis_used`+1 WHERE player_id={$playerId}");
    }

    public static function addPermitRefusal($playerId)
    {
        self::DbQuery("UPDATE lower_sheet SET `permit_refusal`=`permit_refusal`+1 WHERE player_id={$playerId}");
    }


    public static function computeTemporaryWorkersRankings()
    {
        $numberPerPlayer = self::getCollectionFromDB("SELECT player_id, temps_hired FROM lower_sheet", true);
        // Could be done via array_map() with all lowersheets to remove one SQL Query
        $sortedNumbers = array_values($numberPerPlayer);
        rsort($sortedNumbers);
        $ranking = [];
        foreach ($numberPerPlayer as $playerId => $value) {
            $ranking[$playerId] = array_search($value, $sortedNumbers) + 1;
        }
        return $ranking;
    }

    public function getAlreadyDonePlans()
    {
        return array_keys(array_filter($this->projectScores, function ($score) {
            return $score > 0;
        }));
    }

    public function hasAchievedAllPlans()
    {
        foreach ($this->projectScores as $key => $planScore) {
            if ($planScore == 0)
                return false;
        }
        return true;
    }

    public function hasThreePermitRefusals()
    {
        return ($this->permitRefusals == 3);
    }

    public static function getMaxPermitRefusal()
    {
        $maxPermitRefusal = self::getUniqueValueFromDB("SELECT MAX(permit_refusal) FROM lower_sheet");
        return $maxPermitRefusal;
    }

    public static function getMaxPlansDone()
    {
        $sql = "SELECT MAX(Number_of_projects_done) FROM (SELECT IF(`project_1_score` > 0,1,0) + IF(`project_2_score` > 0,1,0) + IF(`project_3_score` > 0,1,0) AS Number_of_projects_done FROM `lower_sheet`) AS Tata";
        $maxNumberOfProjectsDoneByAPlayer = self::getUniqueValueFromDB($sql);
        return $maxNumberOfProjectsDoneByAPlayer;
    }
}
