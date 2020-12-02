<?php

require_once('WTOConstructionCards.php');
require_once('WTOUpperSheet.php');


class WTOPlanValidationInstruction extends APP_GameClass
{
    function __construct($playerId, $stackNumber, $stackAction, $house, $roundabout, $actionName, $newFence)
    {
        $this->playerId = $playerId;
        $this->stackNumber = $stackNumber;
        $this->stackAction = $stackAction;
        $this->house = $house;
        $this->roundabout = $roundabout;
        $this->actionName = $actionName;
        $this->newFence = $newFence;
    }

    public static function buildFromGameAction($playerId, $stackNumber, $stackAction, $house, $roundabout, $action): WTOTurnInstruction
    {
        $contructionCards = new WTOConstructionCards(false, false);
        $actionName = $action['useAction'] ? $contructionCards->getCardAction($stackAction) : 'none';
        return new self($playerId, $stackNumber, $stackAction, $house, $roundabout, $actionName, $action['newFence']);
    }

    public function checkInstructionIsValid()
    {
        $contructionCards = new WTOConstructionCards(false, false);
        $number = $contructionCards->getCardNumber($this->stackNumber);
        $playerSheet = WTOUpperSheet::getPlayerSheet($this->playerId);
        $playerSheet->checkCanPlaceHouse($number, $this->house); // TBD : Ask with new roundabout.
        $this->checkAction($playerSheet);
        return true;
    }

    private function checkAction($playerSheet)
    {
        switch ($this->actionName) {
            case "none";
                return true;
            case 'Surveyor':
                self::dump("debug", $this);
                if (is_null($this->newFence))
                    throw new BgaUserException(sprintf(_("With the surveyor action, you must select where to create a fence.")));
                $playerSheet->checkCanPlaceFence($this->newFence);
                return true;
        }
        throw new BgaUserException(sprintf(_("Selected action is {$this->actionName}, not yet implemented.")));
    }

    public function saveInstruction()
    {
        // cannot_play=False, is_zombie=False, 
        $roundaboutSQL = $this->roundabout ? 1 : 0;
        $newFenceSQL = is_null($this->newFence) ? 'null' : $this->newFence;

        self::DbQuery("INSERT INTO turn_instruction (`player_id`, `stack_number`, `stack_action`, `house_id`, `roundabout`, `action_name`, `new_fence` )
        VALUES({$this->playerId}, {$this->stackNumber}, {$this->stackAction}, {$this->house}, {$roundaboutSQL}, '{$this->actionName}' ,{$newFenceSQL} )");
    }


    public function applyToSheet()
    {
        // StackNumber to Card Number
        $contructionCards = new WTOConstructionCards(false, false);
        $number = $contructionCards->getCardNumber($this->stackNumber);
        WTOUpperSheet::buildHouse($this->playerId, $this->house, $number);
        // Apply houses / roundabout / fence construction
        switch ($this->actionName) {
            case "none";
                break;
            case 'Surveyor':
                WTOUpperSheet::buildFence($this->playerId, $this->newFence);
                break;
        }

        // Apply scoring stuff
    }

    public static function loadInstructionsFromDB(): array
    {
        return array_map(function ($args) {
            return new self(...array_values($args));
        }, self::getCollectionFromDB("SELECT * FROM turn_instruction"));
    }

    public static function cleanTurnInstructions()
    {
        self::DbQuery("TRUNCATE turn_instruction");
    }
}
