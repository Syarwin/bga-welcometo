<?php

class WTOHousingEstate
{
    function __construct($playerId, $houses, $start, $end)
    {
        $this->playerId = $playerId;
        $this->houses = $houses;
        $this->start = $start; // First houseId to be in
        $this->end = $end; // Last houseId to be in
    }

    public function getSize()
    {
        return $this->end - $this->start + 1;
    }

    public function canBeUsedInPlan()
    {
        foreach ($this->houses as $key => $house) {
            if ($house['used_in_plan'])
                return false;
            if (is_null($house['number']))
                return false;
        }
        return true;
    }

    public function isFullyConstructed()
    {
        foreach ($this->houses as $key => $house) {
            if (is_null($house['number']))
                return false;
        }
        return true;
    }

    public function getAllHousesId()
    {
        return array_values(array_map(function ($house) {
            return $house['house_id'];
        }, $this->houses));
    }
}
