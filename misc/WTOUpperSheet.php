<?php

require_once('WTOHousingEstate.php');

class WTOUpperSheet extends APP_GameClass
{
    function __construct($playerId, $houses)
    {
        $this->playerId = $playerId;
        $this->houses = $houses;
        $this->housesWithPool = [2, 6, 7, 10, 13, 17, 22, 27, 31];
    }

    public static function setupUpperSheets($players)
    {
        $sql = "INSERT INTO houses (`player_id`, `house_id`, `number`, `pool_built`, `estate_fence_on_right`, `used_in_plan`) VALUES ";
        $values = [];
        foreach ($players as $playerId => $player) {
            for ($houseId = 0; $houseId < 33; ++$houseId) {
                $values[] = "({$playerId}, {$houseId}, NULL ,False, False, False)";
            }
        }
        $sql .= implode($values, ',');
        self::DbQuery($sql);
    }

    public function format()
    {
        return $this->houses;
    }

    public function checkCanPlaceHouse($number, $houseId, $newRoundaboutId)
    {
        $newHouseStreetPart = $this->getStreetPart($houseId, $newRoundaboutId);
        // Check nothing there already
        foreach ($this->houses as $key => $house) {
            if (is_null($house['number']))
                continue;

            if (($houseId == $house['house_id']) and !is_null($house['number']))
                throw new BgaUserException(sprintf(_("This house has already been built.")));

            if (($house['house_id'] >= $newHouseStreetPart['start']) and ($house['house_id'] <= $newHouseStreetPart['end'])) {
                if (($house['house_id'] > $houseId) and ($house['number'] <= $number)) {
                    throw new BgaUserException(sprintf(_("You must place your houses in ascending order : a smaller house already exists after this one.")));
                }
                if (($house['house_id'] < $houseId) and ($house['number'] >= $number)) {
                    throw new BgaUserException(sprintf(_("You must place your houses in ascending order : a bigger house already exists before this one.")));
                }
            }
        }
    }

    public function checkCanPlaceRoundabout($houseId, $isAdvancedGame)
    {
        if (!is_null($houseId)) {
            if ($isAdvancedGame == false)
                throw new BgaUserException(sprintf(_("You can only place roundabouts in games with the advanced variant.")));
            if (!is_null($this->houses[$houseId]['number']))
                throw new BgaUserException(sprintf(_("There is already a house where you want to place the roundabout.")));
        }
        return true;
    }

    public function checkCanPlaceFence($houseId)
    {
        // Vérifier pas déjà placé
        if ($this->houses[$houseId]['estate_fence_on_right']) {
            throw new BgaUserException(sprintf(_("There is already a fence here.")));
        }

        $endsOfStreet = [9, 20, 32];
        if (in_array($houseId, $endsOfStreet))
            throw new BgaUserException(sprintf(_("There is already a fence at the end of each streets.")));

        if (($this->houses[$houseId]['used_in_plan'] == 1) && ($this->houses[$houseId + 1]['used_in_plan'] == 1))
            throw new BgaUserException(sprintf(_("You can't subdivide an estate that has already been used in a plan.")));
    }

    public function checkCanBuildPool($houseId)
    {
        if (!in_array($houseId, $this->housesWithPool))
            throw new BgaUserException(sprintf(_("You can only build a pool on houses where there is a pool planned, but you can always open a house without using its effect.")));
        return true;
    }

    public function checkCanBuildBisHouse($houseId, $direction)
    {
        if (!is_null($this->houses[$houseId]['number']))
            throw new BgaUserException(sprintf(_("You can only build a bis house on an empty house.")));

        foreach ($this->getStreetsParts() as $key => $streetPart) {
            if (($houseId == $streetPart['start']) && ($direction == 'left'))
                throw new BgaUserException(sprintf(_("You cannot copy this house.")));

            if (($houseId == $streetPart['end']) && ($direction == 'right'))
                throw new BgaUserException(sprintf(_("You cannot copy this house.")));
        }
    }

    public function getStreetsParts($newRoundaboutHouseId = null): array
    {
        $roundabouts = $this->getRoundaboutsHouseIds();
        if (!is_null($newRoundaboutHouseId))
            $roundabouts[] = $newRoundaboutHouseId;

        $streetParts = [
            array('start' => 0, 'end' => 9),
            array('start' => 10, 'end' => 20),
            array('start' => 21, 'end' => 32)
        ];
        foreach ($roundabouts as $key => $roundaboutHouseId) {
            $newStreetParts = [];
            foreach ($streetParts as $spId => $streetPart) {
                if (($roundaboutHouseId >= $streetPart['start']) && ($roundaboutHouseId <= $streetPart['end'])) {
                    if ($streetPart['start'] < $roundaboutHouseId)
                        $newStreetParts[] = array('start' => $streetPart['start'], 'end' => $roundaboutHouseId - 1);
                    if ($streetPart['end'] > $roundaboutHouseId)
                        $newStreetParts[] = array('start' => $roundaboutHouseId + 1, 'end' => $streetPart['end']);
                } else {
                    $newStreetParts[] = $streetPart;
                }
            }
            $streetParts = $newStreetParts;
        }
        return $streetParts;
    }

    public function getRoundaboutsHouseIds(): array
    {
        $roundabouts = [];
        foreach ($this->houses as $houseId => $house) {
            if ($house['number'] == 500)
                $roundabouts[] = $houseId;
        }
        return $roundabouts;
    }

    public static function getStreet($houseId)
    {
        if ($houseId <= 9)
            return 0;
        if ($houseId <= 20)
            return 1;
        if ($houseId <= 32)
            return 2;
    }

    public function getHousingEstates()
    {
        // This function returns housing possible housing estates, whether their houses are built or not.
        $streetParts = $this->getStreetsParts();
        $housingEstates = [];
        foreach ($streetParts as $key => $streetPart) {
            $housingEstateStart = $streetPart['start'];
            for ($houseId = $streetPart['start']; $houseId <= $streetPart['end']; $houseId++) {
                if (($this->houses[$houseId]['estate_fence_on_right']) or ($houseId == $streetPart['end'])) {
                    $housingEstateSize = ($houseId - $housingEstateStart + 1);
                    if ($housingEstateSize <= 6) {
                        $housingEstates[] = new WTOHousingEstate($this->playerId, array_slice($this->houses, $housingEstateStart, $housingEstateSize), $housingEstateStart, $houseId);
                    }
                    $housingEstateStart = $houseId + 1;
                }
            }
        }
        return $housingEstates;
    }

    public function getCompleteHousingEstatesPerSize()
    {
        $housingEstatesPerSize = [0, 0, 0, 0, 0, 0, 0];
        foreach ($this->getHousingEstates() as $key => $housingEstate) {
            if ($housingEstate->isFullyConstructed())
                $housingEstatesPerSize[$housingEstate->getSize()] += 1;
        }

        return $housingEstatesPerSize;
    }

    public function getUnusedHousingEstates()
    {
        return array_values(array_filter($this->getHousingEstates(), function ($housingEstate) {
            return $housingEstate->canBeUsedInPlan();
        }));
    }

    public function getUnusedHousingEstatesNumberPerSize($filterIn = null, $filterOut = array())
    {
        $unusedHousingEstatesPerSize = [0, 0, 0, 0, 0, 0, 0];
        foreach ($this->getUnusedHousingEstates() as $key => $housingEstate) {
            if (!is_null($filterIn) && !in_array($housingEstate->start, $filterIn))
                continue;
            if (in_array($housingEstate->start, $filterOut))
                continue;
            $unusedHousingEstatesPerSize[$housingEstate->getSize()] += 1;
        }
        return $unusedHousingEstatesPerSize;
    }

    public function getStreetPart($houseId, $newRoundaboutId)
    {
        foreach ($this->getStreetsParts($newRoundaboutId) as $key => $street) {
            if (($houseId >= $street['start']) and ($houseId <= $street['end']))
                return $street;
        }
    }

    public static function getPlayerSheet($playerId): WTOUpperSheet
    {
        $houses = self::getObjectListFromDB("SELECT * FROM houses WHERE player_id={$playerId}");
        return new self($playerId, $houses);
    }

    public static function getAllPlayerSheets(): array
    {
        $houses = self::getObjectListFromDB("SELECT * FROM houses");
        $housesPerPlayer = [];
        foreach ($houses as $key => $house) {
            $housesPerPlayer[$house['player_id']][] = $house;
        }
        $sheets = [];
        foreach ($housesPerPlayer as $playerId => $playerHouses) {
            $sheets[$playerId] = new self($playerId, $playerHouses);
        }
        return $sheets;
    }

    public static function buildHouse($playerId, $houseId, $number)
    {
        self::DbQuery("UPDATE houses SET `number`=$number WHERE player_id={$playerId} AND house_id={$houseId}");
        // $playerName = "TBD";
        // $streetNumber = "3rd"; // TBD
        // self::notifyAllPlayers("houseBuilt", clienttranslate('Player {$player_name} has built the house number {$house_number}, on the {$street_number} street'), array(
        //     'house_id' => $houseId,
        //     'house_number' => $number,
        //     'street_number' => $streetNumber,
        //     'player_name' => $playerName
        // ));
    }

    public static function buildFence($playerId, $fenceHouseId)
    {
        $streetEndings = [9, 20, 32];
        if (!in_array($fenceHouseId, $streetEndings))
            self::DbQuery("UPDATE houses SET `estate_fence_on_right`=True WHERE player_id={$playerId} AND house_id={$fenceHouseId}");
    }

    public static function buildRoundabout($playerId, $roundaboutHouseId)
    {
        self::DbQuery("UPDATE houses SET `number`=500 WHERE player_id={$playerId} AND house_id={$roundaboutHouseId}");
        self::buildFence($playerId, $roundaboutHouseId);
        self::buildFence($playerId, $roundaboutHouseId - 1);
    }

    public static function buildPool($playerId, $houseId)
    {
        self::DbQuery("UPDATE houses SET `pool_built`=True WHERE player_id={$playerId} AND house_id={$houseId}");
    }

    public function buildBisHouse($houseId, $direction)
    {
        $copyId = ($direction == 'left') ? $houseId - 1 : $houseId + 1;
        $number = $this->houses[$copyId]['number'];
        self::DbQuery("UPDATE houses SET `number`=$number, `is_bis`=True WHERE player_id={$this->playerId} AND house_id={$houseId}");
        $streetNumber = [_("first"), _("second"), _("third")][self::getStreet($houseId)];
        $notification = ["bisHouseBuilt", clienttranslate('Player ${player_name} has built a second house number ${house_number}bis on the ${street_number} street.'), array(
            'player_id' => $this->playerId,
            'house_number' => $number,
            'house_id' => $houseId,
            'street_number' => $streetNumber,
        )];
        return $notification;
    }

    public static function setHousesEstatesUsedInPlan($playerId, $housingEstates)
    {
        if (empty($housingEstates))
            return [];
        foreach (self::getPlayerSheet($playerId)->getHousingEstates() as $key => $housingEstate) {
            if (in_array($housingEstate->start, $housingEstates))
                $allHousesToValidate = array_merge($housingEstate->getAllHousesId(), $allHousesToValidate);
        }
        $allHousesSQL = "(" . implode(",", $allHousesToValidate) . ")";
        self::DbQuery("UPDATE houses SET `used_in_plan`=True WHERE player_id={$playerId} AND house_id IN {$allHousesSQL}");
        return $allHousesToValidate;
    }

    public static function setHousesUsedInAdvancedPlan($playerId, $houses)
    {
        if (empty($houses))
            return;
        $allHousesSQL = "(" . implode(",", $houses) . ")";
        self::DbQuery("UPDATE houses SET `used_in_plan`=True WHERE player_id={$playerId} AND house_id IN {$allHousesSQL}");
    }

    public function hasBuiltAllHouses($streetId = null)
    {
        foreach ($this->houses as $key => $house) {
            if (!is_null($streetId) && (self::getStreet($house['house_id']) != $streetId))
                continue;
            if (is_null($house['number']))
                return false;
        }
        return true;
    }

    public function hasBuiltAndNotUsedHouses($houses = [])
    {
        foreach ($this->houses as $key => $house) {
            if (!in_array($house['house_id'], $houses))
                continue;
            if (is_null($house['number']) || $house['used_in_plan']) {
                return false;
            }
        }
        return true;
    }

    public function hasAllPoolsBuilt($streetId = null)
    {
        foreach ($this->houses as $key => $house) {
            if (!is_null($streetId) && (self::getStreet($house['house_id']) != $streetId))
                continue;
            if (in_array($house['house_id'], $this->housesWithPool) && !$house['pool_built'])
                return false;
        }
        return true;
    }

    public function hasARoundabout($streetId = null)
    {
        foreach ($this->houses as $key => $house) {
            if (!is_null($streetId) && (self::getStreet($house['house_id']) != $streetId))
                continue;
            if ($house['number'] == 500)
                return true;
        }
        return false;
    }

    public function getTieBreaker()
    {
        $tieBreaker = 0;
        $nbHousingEstates = 0;
        foreach ($this->getCompleteHousingEstatesPerSize() as $size => $housingEstateNumber) {
            $nbHousingEstates += $housingEstateNumber;
            if ($size < 6)
                $tieBreaker += $housingEstateNumber * (100 ** (6 - $size));
        }
        $tieBreaker += $nbHousingEstates * (100 ** (6));
        return $tieBreaker;
    }

    public function canPlay($availableCardNumbers)
    {
        $possibleNumbers = $this->getPossibleNumber();
        self::dump("canPlay possible Numbers", $possibleNumbers);
        $playableNumbersNumber = count(array_intersect($availableCardNumbers, $possibleNumbers));
        return $playableNumbersNumber > 0;
    }

    private function getPossibleNumber()
    {
        $possibleNumbers = [];
        foreach ($this->getStreetsParts() as $key => $streetPart) {
            $lastNumberSeen = -1;
            $hole = false;
            for ($houseId = $streetPart['start']; $houseId <= $streetPart['end']; $houseId++) {
                if (is_null($this->houses[$houseId]['number'])) {
                    $hole = true;
                    if ($houseId == $streetPart['end']) {
                        for ($i = $lastNumberSeen + 1; $i < 18; $i++) {
                            $possibleNumbers[$i] = true;
                        }
                    }
                } else {
                    if ($hole) {
                        for ($i = $lastNumberSeen + 1; $i < $this->houses[$houseId]['number']; $i++) {
                            $possibleNumbers[$i] = true;
                        }
                        $hole = false;
                    }
                    $lastNumberSeen = $this->houses[$houseId]['number'];
                }
            }
        }
        return array_keys($possibleNumbers);
    }

    public function getBisNumberPerStreet(): array
    {
        $bisNumberPerStreet = [0, 0, 0];
        foreach ($this->houses as $key => $house) {
            if ($house['is_bis'])
                $bisNumberPerStreet[$this->getStreet($house['house_id'])] += 1;
        }
        return $bisNumberPerStreet;
    }

    public function getBisHouses($street): array
    {
        return array_keys(array_filter($this->houses, function ($house) {
            return $house['is_bis'];
        }));
    }

    public static function getMinFreeHouses()
    {
        $minFreeBuilding = self::getUniqueValueFromDB("SELECT Min(free) FROM (SELECT COUNT(*) AS free, player_id FROM houses WHERE `number` IS NULL GROUP BY player_id ) as toto");
        return $minFreeBuilding;
    }
}
