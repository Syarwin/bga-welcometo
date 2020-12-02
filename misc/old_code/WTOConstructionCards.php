<?php

class WTOConstructionCards extends APP_GameClass
{
    function __construct($isExpert, $isSolo, $playerId = null)
    {
        // TBD : Singleton useful?
        $this->constructionCards = self::getNew("module.common.deck");
        $this->constructionCards->init("construction_card");
        $this->constructionCards->autoreshuffle = true;
        $this->isExpert = $isExpert;
        $this->isSolo = $isSolo;
        $this->setPlayerIdAndItsStacks($playerId);
        $this->cardNumberMapping = [
            "! Cards Id shall start from one !",
            1, 1, 1,
            2, 2, 2,
            3, 3, 3, 3,
            4, 4, 4, 4, 4,
            5, 5, 5, 5, 5, 5,
            6, 6, 6, 6, 6, 6, 6,
            7, 7, 7, 7, 7, 7, 7, 7,
            8, 8, 8, 8, 8, 8, 8, 8, 8,
            9, 9, 9, 9, 9, 9, 9, 9,
            10, 10, 10, 10, 10, 10, 10,
            11, 11, 11, 11, 11, 11,
            12, 12, 12, 12, 12,
            13, 13, 13, 13,
            14, 14, 14,
            15, 15, 15
        ];
        $this->cardActionMapping = [
            "! Cards Id shall start from one !",
            "Surveyor", "Landscaper", "Real Estate Agent", "Surveyor", "Landscaper",
            "Real Estate Agent", "Surveyor", "Pool Manufacturer", "Temp Agency", "Bis",
            "Pool Manufacturer", "Temp Agency", "Bis", "Landscaper", "Real Estate Agent",
            "Surveyor", "Surveyor", "Landscaper", "Landscaper", "Real Estate Agent",
            "Real Estate Agent", "Surveyor", "Surveyor", "Pool Manufacturer", "Temp Agency",
            "Bis", "Landscaper", "Real Estate Agent", "Surveyor", "Pool Manufacturer",
            "Temp Agency", "Bis", "Landscaper", "Landscaper", "Real Estate Agent",
            "Real Estate Agent", "Surveyor", "Surveyor", "Pool Manufacturer", "Temp Agency",
            "Bis", "Landscaper", "Landscaper", "Real Estate Agent", "Real Estate Agent",
            "Surveyor", "Pool Manufacturer", "Temp Agency", "Bis", "Landscaper",
            "Landscaper", "Real Estate Agent", "Real Estate Agent", "Surveyor", "Surveyor",
            "Pool Manufacturer", "Temp Agency", "Bis", "Landscaper", "Real Estate Agent",
            "Surveyor", "Surveyor", "Landscaper", "Landscaper", "Real Estate Agent",
            "Real Estate Agent", "Pool Manufacturer", "Temp Agency", "Bis", "Landscaper",
            "Real Estate Agent", "Surveyor", "Pool Manufacturer", "Temp Agency", "Bis",
            "Surveyor", "Landscaper", "Real Estate Agent", "Surveyor", "Landscaper",
            "Real Estate Agent"
        ];
        $this->soloCardId = 500;
        $this->playWithThreeCards = ($isSolo or $isExpert);
    }

    public function setPlayerIdAndItsStacks($playerId)
    {
        $this->playerId = $playerId;
        if (is_null($this->playerId))
            $this->stacks = ['stack0', 'stack1', 'stack2'];
        else
            $this->stacks = ["{$playerId}stack0", "{$playerId}stack1", "{$playerId}stack2"];
    }

    public function setupConstructionCards($playerIds = null)
    {
        $this->basicSetup();
        if (!$this->playWithThreeCards)
            // Set-up an initial card in each stack, new Turn, will bring the second one.
            $this->drawNewCards();
        if ($this->isExpert)
            $this->specificExpertSetup($playerIds);
    }

    public function basicSetup()
    {
        // Create cards templates for each color.
        $constructionCards = array(1 => array(), 2 => array(), 3 => array());
        $constructionCards = array();
        for ($value = 1; $value <= 81; $value++) {
            $constructionCards[] = array('type' => $value, 'type_arg' => 0, 'nbr' => 1);
        }
        if ($this->isSolo)
            $constructionCards[] = array('type' => $this->soloCardId, 'type_arg' => 0, 'nbr' => 1);
        $this->constructionCards->createCards($constructionCards, 'deck');
        $this->constructionCards->shuffle('deck');

        if ($this->isSolo) {
            $this->putSoloCardInSecondHalfOfThePacket();
        }
    }

    private function specificExpertSetup($playerIds)
    {
        foreach ($playerIds as $key => $playerId) {
            $this->constructionCards->pickCardForLocation('deck', "for_{$playerId}");
        }
    }

    public function prepareGiveCard($stackToGive, $playerToGive)
    {
        foreach ($this->stacks as $stackId => $stack) {
            // Move the card to give in a special stack, and discards the others.
            if ($stackId == $stackToGive)
                $this->constructionCards->moveAllCardsInLocation($stack, "for_{$playerToGive}", 0);
            else
                $this->constructionCards->moveAllCardsInLocation($stack, 'discard', 0);
        }
    }

    private function putSoloCardInSecondHalfOfThePacket()
    {
        $halfPacketLocationArg = 42;
        $sql = "SELECT card_id id, card_location_arg FROM construction_card WHERE card_type = {$this->soloCardId} ORDER BY card_location_arg DESC";
        $soloCard = array_values(self::getCollectionFromDb($sql))[0];
        if ($soloCard['card_location_arg'] >= $halfPacketLocationArg) {
            // If we are in the top of the packet (highest location_arg), then we put it to the bottom.
            $this->constructionCards->insertCard($soloCard['id'], 'deck', $soloCard['card_location_arg'] - $halfPacketLocationArg);
        }
    }

    public function drawNewCards()
    {
        $drawnCards = [];
        $soloCardDrawn = false;
        foreach ($this->stacks as $stackId => $stack) {
            if ($this->playWithThreeCards) {
                $this->constructionCards->moveAllCardsInLocation($stack, 'discard', 0);
            } else {
                // Discard last flipped card if any, flip the current construction card if any, draw a new casino card
                $this->constructionCards->moveAllCardsInLocation($stack, 'discard', 1);
                $this->constructionCards->moveAllCardsInLocation($stack, $stack, 0, 1);
            }
            if (($stackId == 0) && ($this->isExpert))
                $drawnCard = $this->constructionCards->pickCardForLocation("for_{$this->playerId}", $stack);
            else
                $drawnCard = $this->constructionCards->pickCardForLocation('deck', $stack);

            if ($drawnCard['type'] == $this->soloCardId) {
                $this->constructionCards->moveCard($drawnCard['id'], 'removed');
                $drawnCard = $this->constructionCards->pickCardForLocation('deck', $stack);
                $soloCardDrawn = true;
            }
            $drawnCards[$stackId] = $this->formatCard($drawnCard);
        }
        return array('drawnCards' => $drawnCards, 'soloCardDrawn' => $soloCardDrawn);
    }

    public function getConstructionCards(): array
    {
        $constructionCards = [];
        foreach ($this->stacks as $stackId => $stack) {
            $constructionCardsInStack = $this->constructionCards->getCardsInLocation($stack);
            $constructionCards[$stackId] = array_values(array_map(array($this, 'formatCard'), $constructionCardsInStack));
        }
        return $constructionCards;
    }

    private function formatCard($constructionCard)
    {
        return array('id' => $constructionCard['type'], 'flipped' => boolval($constructionCard['location_arg']));
    }

    public function getCardNumber($stackId): int
    {
        $card = array_values($this->constructionCards->getCardsInLocation($this->stacks[$stackId], 0))[0];
        return $this->cardNumberMapping[$card['type']];
    }

    public function getCardAction($stackId): string
    {
        if ($this->playWithThreeCards)
            $card = array_values($this->constructionCards->getCardsInLocation($this->stacks[$stackId], 0))[0];
        else
            $card = array_values($this->constructionCards->getCardsInLocation($this->stacks[$stackId], 1))[0];
        return $this->cardActionMapping[$card['type']];
    }

    public function getAvailableNumbers(): array
    {
        if ($this->playWithThreeCards)
            return $this->getAvailableNumberInThreeCardsMode();
        else
            return $this->getAvailableNumberInRegularMode();
    }

    private function getAvailableNumberInThreeCardsMode(): array
    {
        $availableNumbers = [];
        $numbersByStack = [];
        for ($stackId = 0; $stackId < 3; $stackId++) {
            $actionsByStack[$stackId] = $this->getCardAction($stackId);
            $numbersByStack[$stackId] = $this->getCardNumber($stackId);
        }
        for ($stackIdAction = 0; $stackIdAction < 3; $stackIdAction++) {
            $action = $this->getCardAction($stackIdAction);
            for ($stackIdNumber = 0; $stackIdNumber < 3; $stackIdNumber++) {
                $number = $numbersByStack[$stackIdNumber];
                if (!($stackIdNumber == $stackIdAction)) {
                    // Foreach legit pair of action and number
                    if ($action == "Temp Agency") {
                        for ($i = -2; $i <= 2; $i++) {
                            if ($number + $i >= 0)
                                $availableNumbers[$number + $i] = true;
                        }
                    } else
                        $availableNumbers[$number] = true;
                }
            }
        }
        return array_keys($availableNumbers);
    }

    private function getAvailableNumberInRegularMode(): array
    {
        $availableNumbers = [];
        for ($stackId = 0; $stackId < 3; $stackId++) {
            $number = $this->getCardNumber($stackId);
            $action = $this->getCardAction($stackId);
            $availableNumbers[$number] = true;
            if ($action == "Temp Agency") {
                for ($i = -2; $i <= 2; $i++) {
                    if ($number + $i >= 0)
                        $availableNumbers[$number + $i] = true;
                }
            }
        }
        return array_keys($availableNumbers);
    }
}
