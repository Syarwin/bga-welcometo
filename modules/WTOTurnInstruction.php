<?php

require_once('WTOConstructionCards.php');
require_once('WTOUpperSheet.php');


class WTOTurnInstruction extends APP_GameClass
{
    function __construct($constructionCardsInstance, $playerId, $stackNumber, $stackAction, $house, $roundabout, $permitRefusal, $actionName, $newFence, $estateSizeUpgrade, $delta, $bisHouseId, $bisCopyFrom)
    {
        $this->playerId = $playerId;
        $this->stackNumber = $stackNumber;
        $this->stackAction = $stackAction;
        $this->house = $house;
        $this->roundabout = $roundabout;
        $this->permitRefusal = $permitRefusal;
        $this->actionName = $actionName;
        $this->newFence = $newFence;
        $this->estateSizeUpgrade = $estateSizeUpgrade;
        $this->delta = $delta;
        $this->bisHouseId = $bisHouseId;
        $this->bisCopyFrom = $bisCopyFrom;
        $this->constructionCards = $constructionCardsInstance;
    }

    public static function buildFromGameAction($playerId, $stackNumber, $stackAction, $house, $roundabout, $permitRefusal, $action, $constructionCardsInstance): WTOTurnInstruction
    {
        $actionName = $action['useAction'] ? $constructionCardsInstance->getCardAction($stackAction) : 'none';
        return new self($constructionCardsInstance, $playerId, $stackNumber, $stackAction, $house, $roundabout, $permitRefusal, $actionName, $action['newFence'], $action['estateSizeUpgrade'], $action['delta'], $action['bisHouseId'], $action['bisCopyFrom']);
    }

    public function checkInstructionIsValid($isAdvancedGame)
    {
        $this->checkStackSelectionFollowRules();
        if ($this->permitRefusal)
            // TBD : Ensure he really cannot play.
            return true;
        $number = $this->constructionCards->getCardNumber($this->stackNumber);
        $playerUpperSheet = WTOUpperSheet::getPlayerSheet($this->playerId);
        $playerUpperSheet->checkCanPlaceRoundabout($this->roundabout, $isAdvancedGame);
        $playerUpperSheet->checkCanPlaceHouse($number + $this->delta, $this->house, $this->roundabout);
        $this->checkAction($playerUpperSheet);
        return true;
    }

    private function checkStackSelectionFollowRules()
    {
        $playWithThreeCards = $this->constructionCards->playWithThreeCards;
        if ($playWithThreeCards and ($this->stackNumber == $this->stackAction))
            throw new BgaUserException(sprintf(_("You must select two different cards (from different stacks of cards).")));
        if (!$playWithThreeCards and ($this->stackNumber != $this->stackAction))
            throw new BgaUserException(sprintf(_("You must select two cards from the same stacks of cards.")));
    }

    private function checkAction($playerUpperSheet)
    {
        switch ($this->actionName) {
            case "none";
                return true;
            case 'Surveyor':
                if (is_null($this->newFence))
                    throw new BgaUserException(sprintf(_("With the surveyor action, you must select where to create a fence.")));
                $playerUpperSheet->checkCanPlaceFence($this->newFence);
                return true;
            case 'Real Estate Agent':
                if (is_null($this->estateSizeUpgrade))
                    throw new BgaUserException(sprintf(_("With the 'Real Estate Agent' action, you must select where to improve the value of housing estates.")));
                $playerLowerSheet = WTOLowerSheet::getAllPlayerSheets()[$this->playerId];
                $playerLowerSheet->checkCanUpgradeRealEstate($this->estateSizeUpgrade);
                return true;
            case 'Landscaper':
                $street = WTOUpperSheet::getStreet($this->house);
                $playerLowerSheet = WTOLowerSheet::getAllPlayerSheets()[$this->playerId];
                $playerLowerSheet->checkCanBuildPark($street);
                return true;
            case 'Pool Manufacturer':
                $playerUpperSheet->checkCanBuildPool($this->house);
                return true;
            case 'Temp Agency':
                $playerLowerSheet = WTOLowerSheet::getAllPlayerSheets()[$this->playerId];
                $playerLowerSheet->checkCanUseTemp();
                return true;
            case 'Bis':
                if (is_null($this->bisHouseId) or is_null($this->bisCopyFrom))
                    throw new BgaUserException(sprintf(_("With the Bis action, you must choose which house to copy and where.")));
                $playerUpperSheet->checkCanBuildBisHouse($this->bisHouseId, $this->bisCopyFrom);

                $playerLowerSheet = WTOLowerSheet::getAllPlayerSheets()[$this->playerId];
                $playerLowerSheet->checkCanUseBis();

                return true;
        }
        throw new BgaUserException(sprintf(_("Selected action is {$this->actionName}, not yet implemented.")));
    }

    public function saveInstruction()
    {
        // cannot_play=False, is_zombie=False, 
        $roundaboutSQL = is_null($this->roundabout) ? 'null' : $this->roundabout;
        $permitRefusalSQL = $this->permitRefusal ? 1 : 0;
        $newFenceSQL = is_null($this->newFence) ? 'null' : $this->newFence;
        $estateSizeUpgradeSQL = is_null($this->estateSizeUpgrade) ? 'null' : $this->estateSizeUpgrade;
        $bisHouseIdSQL = is_null($this->bisHouseId) ? 'null' : $this->bisHouseId;
        $bisCopyFromSQL = is_null($this->bisCopyFrom) ? 'null' : "'{$this->bisCopyFrom}'";

        self::DbQuery("INSERT INTO turn_instruction (`player_id`, `stack_number`, `stack_action`, `house_id`, `roundabout`, `action_name`, `new_fence`, `estate_size_upgrade`, `delta`, `bis_house_id`, `bis_copy_from`, `permit_refusal` )
        VALUES({$this->playerId}, {$this->stackNumber}, {$this->stackAction}, {$this->house}, {$roundaboutSQL}, '{$this->actionName}' ,{$newFenceSQL}, {$estateSizeUpgradeSQL}, {$this->delta}, {$bisHouseIdSQL}, {$bisCopyFromSQL}, {$permitRefusalSQL} )");
    }


    public function applyToSheet()
    {
        $notifications = [];
        $stats = [];
        if ($this->permitRefusal) {
            $stats[] = array("incNumber" => 1, "label" => 'permit_refusal_number', "playerId" => $this->playerId);
            WTOLowerSheet::addPermitRefusal($this->playerId);
            $notifications[] =  ["permitRefusal", clienttranslate('Player ${player_name} cannot play and received a permit refusal.'), array()];
            return $notifications;
        }
        // StackNumber to Card Number
        $number = $this->constructionCards->getCardNumber($this->stackNumber) + $this->delta;
        WTOUpperSheet::buildHouse($this->playerId, $this->house, $number);
        $streetName = [_("first street"), _("second street"), _("third street")][WTOUpperSheet::getStreet($this->house)];
        $notifications[] = ["houseBuilt", clienttranslate('Player ${player_name} has built the house number ${house_number}, on the ${street_name}'), array(
            'i18n' => array('street_name'),
            'house_id' => $this->house,
            'house_number' => $number,
            'street_name' => $streetName,
        )];
        $nbHousesBuilt = ($this->actionName == 'Bis') ? 2 : 1;
        $stats[] = array("incNumber" => $nbHousesBuilt, "label" => 'houses_opened_number', "playerId" => $this->playerId);
        if (!is_null($this->roundabout)) {
            WTOUpperSheet::buildRoundabout($this->playerId, $this->roundabout);
            $roundaboutStreetName = [_("first street"), _("second street"), _("third street")][WTOUpperSheet::getStreet($this->roundabout)];
            $notifications[] = ["roundaboutBuilt", clienttranslate('Player ${player_name} has built a new roundabout, on the ${street_name}'), array(
                'i18n' => array('street_name'),
                'roundabout_house_id' => $this->roundabout,
                'street_name' => $roundaboutStreetName,
            )];
        }
        switch ($this->actionName) {
            case "none";
                break;
            case 'Surveyor':
                WTOUpperSheet::buildFence($this->playerId, $this->newFence);
                $streetName = [_("first street"), _("second street"), _("third street")][WTOUpperSheet::getStreet($this->newFence)];
                $notifications[] = ["fenceBuilt", clienttranslate('Player ${player_name} has built a fence between two houses on the ${street_name}'), array(
                    'i18n' => array('street_name'),
                    'fence_id' => $this->newFence,
                    'street_name' => $streetName,
                )];
                break;
            case 'Real Estate Agent':
                WTOLowerSheet::upgradeRealEstate($this->playerId, $this->estateSizeUpgrade);
                $notifications[] = ["realEstatePromoted", clienttranslate('Player ${player_name} has increased the value of its housing estates of size ${size}.'), array(
                    'size' => $this->estateSizeUpgrade,
                )];
                break;
            case 'Landscaper':
                $street = WTOUpperSheet::getStreet($this->house);
                $streetName = [_("first street"), _("second street"), _("third street")][$street];
                WTOLowerSheet::buildPark($this->playerId, $street);
                $notifications[] = ["parkBuilt", clienttranslate('Player ${player_name} has built a lovely park on the ${street_name}.'), array(
                    'i18n' => array('street_name'),
                    'street' => $street,
                    'street_name' => $streetName,
                )];
                break;
            case 'Pool Manufacturer':
                WTOLowerSheet::buildPool($this->playerId);
                WTOUpperSheet::buildPool($this->playerId, $this->house);
                $notifications[] = ["poolBuilt", clienttranslate('Player ${player_name} has built a new pool with its house.'), array(
                    'house_id' => $this->house,
                )];
                break;
            case 'Temp Agency':
                WTOLowerSheet::hireTemporaryWorker($this->playerId);
                $notifications[] = ["temporaryWorkerHired", clienttranslate('Player ${player_name} has hired a temporary worker.'), array()];
                break;
            case 'Bis':
                WTOLowerSheet::useBis($this->playerId);
                $upperSheet = WTOUpperSheet::getPlayerSheet($this->playerId);
                $bisHouseNotification = $upperSheet->buildBisHouse($this->bisHouseId, $this->bisCopyFrom);
                $notifications[] = $bisHouseNotification;
                break;
        }
        $statActionMapping = array("none" => "no_effect_number", 'Surveyor' => "surveyor_number", 'Real Estate Agent' => "real_estate_number", 'Landscaper' => "landscaper_number", 'Pool Manufacturer' => "pool_manufacturer_number", 'Temp Agency' =>  "temp_agency_number", 'Bis' => "bis_number");
        $stats[] = array("incNumber" => 1, "label" => $statActionMapping[$this->actionName], "playerId" => $this->playerId);

        // Apply scoring stuff

        return array('notifications' => $notifications, 'stats' => $stats);
    }

    public static function loadInstructionsFromDB($constructionCardsInstance): array
    {
        return array_map(function ($args) use ($constructionCardsInstance) {
            return new self($constructionCardsInstance, ...array_values($args));
        }, self::getCollectionFromDB("SELECT * FROM turn_instruction"));
    }

    public static function cleanTurnInstructions()
    {
        self::DbQuery("TRUNCATE turn_instruction");
    }

    public function prepareGiveCard($playerToGive)
    {
        $unusedStack = array_values(array_diff([0, 1, 2], [$this->stackNumber, $this->stackAction]))[0];
        $this->constructionCards->prepareGiveCard($unusedStack, $playerToGive);
    }
}
