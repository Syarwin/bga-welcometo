define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",], function (dojo, declare) {
        return declare("bgagame.wtoScoreSheet", ebg.core.gamegui, {
            constructor: function (player, gameData, parentDiv, gameui, readonly = false) {
                console.log("HI from constructor wtoScoreSheet");
                this.gameData = gameData;
                this.gameui = gameui;
                this.player = player;
                this.readonly = readonly;
                this.streetParts = [];
                this.availableHousingEstates = [];
                this.selectedHousingEstates = [];
                this.builtHousesIds = [];
                this.placementDict = this._buildHousePlacementDict();
                this.realEstatesPossibleUpgrades = { 1: 1, 2: 2, 3: 3, 4: 4, 5: 4, 6: 4 };
                this.realEstatesUpgradesNumberDone = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0 };
                this.parksUpgradesNumberDone = [0, 0, 0];
                this.poolsBuilt = 0;
                this.tempsHired = 0;
                this.bisUsed = 0;
                this.permitRefusalNumber = 0;
                this.roundaboutsBuilt = 0;
                this.sheetDiv = dojo.place(gameui.format_block('score_sheet_div', { 'player_id': this.player.id }), parentDiv);
                this._setupHouses(gameData);
                this.setupTopFences(gameData);
                this.setupEstatesFences(gameData);
                this.setupRoundAboutsScore(lowerSheetData);
                this.fillUpperSheet(gameData.upper_sheets[this.player.id]);
                var lowerSheetData = gameData.lower_sheets[this.player.id];
                this.setupParks(lowerSheetData);
                this.setupPlansScore(lowerSheetData);
                this.setupPoolsScore(lowerSheetData);
                this._setupHousesPools(gameData);
                this.setupRealEstatesScore(lowerSheetData);
                this.setupTempAgencyScore(lowerSheetData);
                this.setupBisScore(lowerSheetData);
                this.setupPermitRefusalsScore(lowerSheetData);
                var scoreData = gameData.scores[this.player.id];
                this.setupResultingScores(scoreData);
            },

            _manageTentativeAction(div, counter, isTentative) {
                dojo.addClass(div, "upgraded");
                if (isTentative) {
                    this.clearActionInput();
                    dojo.addClass(div, "tentative");
                }
                else {
                    counter += 1;
                    dojo.removeClass(div, "tentative");
                }
                return counter;
            },
            /** Houses related functions  */

            _setupHouses: function (gameData) {
                var houses = [...Array(33).keys()];
                for (var house_id in houses) {
                    var house_div = dojo.place(gameui.format_block('house_div', { 'house_id': house_id, 'avenue': this.placementDict[house_id]['avenue'], 'street': this.placementDict[house_id]['street'], 'player_id': this.player.id }), this.sheetDiv);
                    if (!this.readonly)
                        dojo.connect(house_div, 'onclick', this, dojo.partial(this.onHouseSelected, house_id));
                };
            },

            _buildHousePlacementDict: function () {
                var houses_placement_dict = [];

                for (avenue = 2; avenue <= 11; avenue++) {
                    houses_placement_dict.push({ avenue: avenue, street: 0 });
                }
                for (avenue = 1; avenue <= 11; avenue++) {
                    houses_placement_dict.push({ avenue: avenue, street: 1 });
                }
                for (avenue = 0; avenue <= 11; avenue++) {
                    houses_placement_dict.push({ avenue: avenue, street: 2 });
                }
                return houses_placement_dict;
            },

            onHouseSelected: function (houseId, evt) {
                if (this.gameui.clientState.getState() == "ROUNDABOUT_PLACEMENT") {
                    this.onRoundaboutPlaceChoosen(houseId);
                    return;
                }
                if (this.gameui.clientState.stateIsBefore("CARDS_READY")) {
                    this.showMessage("You cannot select where to place a house before choosing its number.", "error");
                    return;
                }
                if (!this.gameui.clientState.stateIsBefore("BIS_SELECTED")) {
                    this.onBisHouseSelected(houseId, evt);
                    return;
                }
                if (this.builtHousesIds.indexOf(houseId) == -1) {
                    if (this.gameui.cardViewer.getSelectedAction() == "Temp Agency") {
                        this.setEasyOpeningChoiceDialog();
                    }
                    this.setUserSelectedHouse(houseId);
                    this.gameui.onHouseSelected();
                }
            },

            setUserSelectedHouse: function (houseId) {
                if (!(this.selectedHouse == undefined))
                    this._removeTentativeNumber(this.selectedHouse);
                this.selectedHouse = houseId;
                this.addNumberToHouse(houseId, this.gameui.cardViewer.getSelectedCardNumber(), true);
            },

            addDeltaToSelectedHouse(delta) {
                this.addNumberToHouse(this.selectedHouse, this.gameui.cardViewer.getSelectedCardNumber() + delta, true);
            },

            addNumberToHouse: function (houseId, number, isTentative = false, isBis = false) {
                var house_div = dojo.byId(`house_${houseId}_${this.player.id}`);
                house_div.textContent = number;
                dojo.removeClass(house_div, 'empty_house');
                if (isTentative)
                    dojo.addClass(house_div, "tentative");
                else {
                    this.builtHousesIds.push(houseId);
                    dojo.removeClass(house_div, "tentative");
                    if (this.selectedHouse == houseId)
                        this.selectedHouse = undefined;
                }
                if (isBis)
                    house_div.textContent = number + "bis";
            },

            _removeTentativeNumber: function (houseId) {
                var house_div = dojo.byId(`house_${houseId}_${this.player.id}`);
                house_div.textContent = "";
                dojo.addClass(house_div, 'empty_house');
                dojo.removeClass(`house_${houseId}_${this.player.id}`, "tentative");
                dojo.removeClass(`house_${houseId}_${this.player.id}`, "bis");
            },

            /** Top fences related funcitons */
            setupTopFences: function (gameData) {
                var houses = [...Array(33).keys()];
                for (var house_id in houses) {
                    var top_fence_div = dojo.place(this.format_block('plan_fence_div', { 'house_id': house_id, 'avenue': this.placementDict[house_id]['avenue'], 'street': this.placementDict[house_id]['street'], 'player_id': this.player.id }), this.sheetDiv);
                    if (!this.readonly)
                        dojo.connect(top_fence_div, 'onclick', this, dojo.partial(this.onTopFenceSelected, house_id));
                };
            },

            fillTopFences: function (houseIds) {
                for (var key in houseIds) {
                    var houseId = houseIds[key];
                    var topFence = dojo.byId(`top_fence_${houseId}_${this.player.id}`);
                    this.gameData.upper_sheets[this.player.id][houseId].used_in_plan = 1;
                    dojo.addClass(topFence, 'used');
                }
            },

            onTopFenceSelected: function (houseId, evt) {
                this.toggleHousingEstate(houseId);
            },

            toggleHousingEstate: function (houseId) {
                var belongingHousingEstate;
                this.availableHousingEstates.forEach(housingEstate => {
                    if ((housingEstate['start'] <= houseId) && (housingEstate['end'] >= houseId))
                        belongingHousingEstate = housingEstate;
                });
                if (belongingHousingEstate) {
                    var heIndex = this.selectedHousingEstates.indexOf(belongingHousingEstate['start'])
                    if (heIndex == -1)
                        this.selectedHousingEstates.push(belongingHousingEstate['start']);
                    else
                        this.selectedHousingEstates.splice(heIndex, 1);
                    for (let houseToToggle = belongingHousingEstate['start']; houseToToggle <= belongingHousingEstate['end']; houseToToggle++) {
                        dojo.toggleClass(`top_fence_${houseToToggle}_${this.player.id}`, "selected");
                    }
                }
            },

            /** Estate fences related funcitons */

            setupEstatesFences: function (gameData) {
                var houses = [...Array(33).keys()];
                for (var house_id in houses) {
                    if (!(this.placementDict[house_id].avenue == 11)) {
                        var estate_fence_div = dojo.place(this.format_block('estate_fence_div', { 'house_id': house_id, 'avenue': this.placementDict[house_id]['avenue'] + 1, 'street': this.placementDict[house_id]['street'], 'player_id': this.player.id }), this.sheetDiv);
                        if (!this.readonly)
                            dojo.connect(estate_fence_div, 'onclick', this, dojo.partial(this.onEstateFenceSelected, house_id));
                    }
                };
            },

            onEstateFenceSelected: function (houseId, evt) {
                if (this.gameui.clientState.stateIsBefore("HOUSE_SELECTED")) {
                    this.showMessage("You must place the house before applying the effect.", "error")
                    return;
                }

                this.fillEstateFence(houseId, true);
                this.gameui.onEstateFenceSelected(houseId);
            },

            fillEstateFence: function (houseId, isTentative = false) {
                console.log("fillEstateFence", houseId);
                var estateFenceDiv = dojo.byId(`estate_fence_${houseId}_${this.player.id}`);
                this._manageTentativeAction(estateFenceDiv, 0, isTentative);
            },

            /** Parks related funcitons */

            setupParks: function (lowerSheetData) {
                for (var streetId = 0; streetId < 3; streetId++) {
                    for (var parkNumber = 0; parkNumber < 3 + streetId; parkNumber++) {
                        var park_div = dojo.place(this.format_block('park_div', { 'park_number': parkNumber, 'street': streetId, 'park_column': 2 + parkNumber - streetId, 'player_id': this.player.id }), this.sheetDiv);
                        if (!this.readonly)
                            dojo.connect(park_div, 'onclick', this, dojo.partial(this.onParkSelected, streetId, parkNumber));
                        if (lowerSheetData.parksBuilt[streetId] > parkNumber) {
                            this.fillPark(streetId);
                        }
                    }
                    dojo.place(this.format_block('park_score_div', { 'street': streetId, 'player_id': this.player.id }), this.sheetDiv);
                }
            },

            onParkSelected: function (streetId, parkNumber, evt) {
                console.log("onParkSelected", streetId, parkNumber);
                if (this.gameui.clientState.stateIsBefore("HOUSE_SELECTED")) {
                    this.showMessage("You must place the house before applying the effect.", "error");
                    return;
                }
                if (!(this.placementDict[this.selectedHouse].street == streetId))
                    this.showMessage("You must check the park on the street in which you built the house associated with the landscaper effect.", "error");
                else if (!(parkNumber == this.parksUpgradesNumberDone[streetId]))
                    this.showMessage("You must check the first available value (from top to bottom).", "error");
                else {
                    this.gameui.onParkSelected(streetId, parkNumber);
                    this.fillPark(streetId, true);
                }
            },

            fillPark: function (streetId, isTentative = false) {
                var parkDiv = dojo.byId(`park_${streetId}_${this.parksUpgradesNumberDone[streetId]}_${this.player.id}`);
                this.parksUpgradesNumberDone[streetId] = this._manageTentativeAction(parkDiv, this.parksUpgradesNumberDone[streetId], isTentative);
            },

            /** Plans related functions */

            setupPlansScore: function (lowerSheetData) {
                for (var planId = 1; planId < 4; planId++) {
                    dojo.place(this.format_block('plan_score_div', { 'plan_id': planId, 'player_id': this.player.id }), this.sheetDiv);
                    this.setPlanScore(planId, lowerSheetData.projectScores[planId]);
                }
            },

            setPlanScore: function (planNumber, score) {
                var planDiv = dojo.byId(`plan_${planNumber}_score_${this.player.id}`);
                planDiv.textContent = score;
            },

            /** Pools related functions */

            setupPoolsScore: function (lowerSheetData) {
                for (var poolNumber = 0; poolNumber < 9; poolNumber++) {
                    var poolDiv = dojo.place(this.format_block('pool_score_div', { 'pool_number': poolNumber, 'pool_line': Math.trunc(poolNumber / 2), 'pool_column': Number(poolNumber % 2), 'player_id': this.player.id }), this.sheetDiv);
                    if (!this.readonly)
                        dojo.connect(poolDiv, 'onclick', this, dojo.partial(this.onPoolSelected, poolNumber));
                }
            },

            onPoolSelected: function (poolNumber, evt) {
                console.log("onPoolSelected", poolNumber);
                if (this.gameui.clientState.stateIsBefore("HOUSE_SELECTED")) {
                    this.showMessage("You must place the house before applying the effect.", "error")
                    return;
                }
                if (!(poolNumber == this.poolsBuilt))
                    this.showMessage("You must check the first available value (from top to bottom).", "error")
                else {
                    this.fillPool(this.getSelectedHouse(), true);
                    this.gameui.onPoolSelected(poolNumber);
                }
            },

            fillPool: function (houseId, isTentative = false) {
                // Score
                var poolDiv = dojo.byId(`pool_${this.poolsBuilt}_score_${this.player.id}`);
                this.poolsBuilt = this._manageTentativeAction(poolDiv, this.poolsBuilt, isTentative);

                // On house
                var housePoolDiv = dojo.byId(`house_pool_${houseId}_${this.player.id}`);
                dojo.addClass(housePoolDiv, "built");
                if (isTentative)
                    dojo.addClass(housePoolDiv, "tentative");
                else
                    dojo.removeClass(poolDiv, "tentative");
            },


            _setupHousesPools: function (gameData) {
                // Warning : this function expects that setup pools score is already done.
                var housesWithPool = [2, 6, 7, 10, 13, 17, 22, 27, 31];
                for (var id in housesWithPool) {
                    var houseId = housesWithPool[id];
                    dojo.place(gameui.format_block('house_pool_div', { 'house_id': houseId, 'avenue': this.placementDict[houseId]['avenue'], 'street': this.placementDict[houseId]['street'], 'player_id': this.player.id }), this.sheetDiv);
                    if (gameData.upper_sheets[this.player.id][houseId].pool_built == 1)
                        this.fillPool(houseId);
                };
            },

            /** Real estate related functions */

            setupRealEstatesScore: function (lowerSheetData) {
                for (var estateSize = 1; estateSize <= 6; estateSize++) {
                    for (var upgradeNumber = 1; upgradeNumber <= this.realEstatesPossibleUpgrades[estateSize]; upgradeNumber++) {
                        var real_estate_div = dojo.place(this.format_block('real_estate_score_div', { 'size': estateSize, 'number': upgradeNumber, 'player_id': this.player.id }), this.sheetDiv);
                        if (!this.readonly)
                            dojo.connect(real_estate_div, 'onclick', this, dojo.partial(this.onRealEstateSelected, estateSize, upgradeNumber));
                        if (lowerSheetData.realEstate[estateSize] >= upgradeNumber) {
                            this.fillRealEstate(estateSize);
                        }
                    }
                    // Number div.
                    dojo.place(this.format_block('real_estate_score_number_div', { 'size': estateSize, 'player_id': this.player.id }), this.sheetDiv);
                }
            },

            onRealEstateSelected: function (estateSize, upgradeNumber, evt) {
                console.log("onRealEstateSelected", estateSize, upgradeNumber);
                if (this.gameui.clientState.stateIsBefore("HOUSE_SELECTED")) {
                    this.showMessage("You must place the house before applying the effect.", "error")
                    return;
                }
                if (!(upgradeNumber == this.realEstatesUpgradesNumberDone[estateSize] + 1))
                    this.showMessage("You must check the first available value (from top to bottom).", "error")
                else {
                    this.gameui.onRealEstateSelected(estateSize, upgradeNumber);
                    this.fillRealEstate(estateSize, true);
                }
            },

            fillRealEstate: function (estateSize, isTentative = false) {
                var upgradeNumber = this.realEstatesUpgradesNumberDone[estateSize] + 1;
                var realEstateDiv = dojo.byId(`real_estate_${estateSize}_${upgradeNumber}_score_${this.player.id}`);
                this.realEstatesUpgradesNumberDone[estateSize] = this._manageTentativeAction(realEstateDiv, this.realEstatesUpgradesNumberDone[estateSize], isTentative);
            },

            /** Temp agency related functions */

            setupTempAgencyScore: function (lowerSheetData) {
                var tempLine = [0, 0, 1, 2, 2, 3, 4, 4, 5, 6, 6];
                // Column 0 and 1 are one the first line, column 2 is in the middle, a little lower.
                for (var tempNumber = 0; tempNumber < 11; tempNumber++) {
                    dojo.place(this.format_block('temp_score_div', { 'temp_number': tempNumber, 'temp_line': tempLine[tempNumber], 'temp_column': Number(tempNumber % 3), 'player_id': this.player.id }), this.sheetDiv);
                    if (lowerSheetData.tempsHired > tempNumber) {
                        this.fillTemp();
                    }
                }
            },

            _getSelectedPossibleNumbers() {
                var possibleCardNumbers = [];
                var cardNumber = this.gameui.cardViewer.getSelectedCardNumber();
                if (this.gameui.cardViewer.getSelectedAction() == 'Temp Agency') {
                    for (var newNumber = Math.max(0, cardNumber - 2); newNumber <= cardNumber + 2; newNumber++) {
                        possibleCardNumbers.push(newNumber);
                    }
                }
                else {
                    possibleCardNumbers.push(cardNumber);
                }
                return possibleCardNumbers;
            },

            setEasyOpeningChoiceDialog() {
                var possibleCardNumbers = this._getSelectedPossibleNumbers();
                var cardNumber = this.gameui.cardViewer.getSelectedCardNumber();
                this.multipleChoiceDialog(
                    _('Which number do you want to use?'), possibleCardNumbers,
                    dojo.hitch(this, function (choice) {
                        var newNumber = possibleCardNumbers[choice];
                        this.onDeltaChosen(newNumber - cardNumber);
                    }));
            },

            onDeltaChosen(delta) {
                console.log("Player chose a delta of ", delta);
                this.addDeltaToSelectedHouse(delta);
                this.fillTemp(true);
                this.gameui.onTempAgencyChosen(delta);
            },

            fillTemp: function (isTentative = false) {
                var tempDiv = dojo.byId(`temp_${this.tempsHired}_score_${this.player.id}`);
                this.tempsHired = this._manageTentativeAction(tempDiv, this.tempsHired, isTentative);
            },

            /** Bis related functions */

            setupBisScore: function (lowerSheetData) {
                for (var bisNumber = 0; bisNumber < 9; bisNumber++) {
                    var bisDiv = dojo.place(this.format_block('bis_score_div', { 'bis_number': bisNumber, 'bis_line': Math.trunc(bisNumber / 2), 'bis_column': Number(bisNumber % 2), 'player_id': this.player.id }), this.sheetDiv);
                    if (!this.readonly)
                        dojo.connect(bisDiv, 'onclick', this, dojo.partial(this.onBisScoreSelected, bisNumber));
                    if (lowerSheetData.bisUsed > bisNumber) {
                        this.fillBis();
                    }
                }
            },

            onBisScoreSelected: function (bisNumber, evt) {
                console.log("onBisScoreSelected", bisNumber);
                if (this.gameui.clientState.stateIsBefore("HOUSE_SELECTED")) {
                    this.showMessage("You must place the house before applying the effect.", "error")
                    return;
                }
                if (!(bisNumber == this.bisUsed))
                    this.showMessage("You must check the first available value (from top to bottom).", "error")
                else {
                    this.fillBis(true);
                    this.gameui.clientState.setState("BIS_SELECTED");
                }
            },

            onBisHouseSelected: function (houseId, evt) {
                this.bisHouseSelected = houseId;
                this.setCopyHouseChoiceDialog(houseId);
            },

            setCopyHouseChoiceDialog: function (houseId) {
                var streetBeginings = [0, 10, 21];
                var keys = [];
                var directions = [];
                // Add left choice if possible
                if (!streetBeginings.includes(Number(houseId))) {
                    var leftNumber = this.getHouseNumber(Number(houseId) - 1);
                    if (!(leftNumber == undefined)) {
                        keys.push(`Copy from left : ${leftNumber}`);
                        directions.push('left');
                    }
                }

                // Add right choice if possible
                var streetEndings = [9, 20, 32];
                if (!streetEndings.includes(Number(houseId))) {
                    var rightNumber = this.getHouseNumber(Number(houseId) + 1);
                    if (!(rightNumber == undefined)) {
                        keys.push(`Copy from right : ${rightNumber}`);
                        directions.push('right');
                    }
                }

                this.multipleChoiceDialog(
                    _('Which number do you want to copy?'), keys,
                    dojo.hitch(this, function (choice) {
                        this.onBisDirectionChosen(directions[choice]);
                    }));
            },

            onBisDirectionChosen: function (direction) {
                var copiedHouse = (direction == 'right') ? Number(this.bisHouseSelected) + 1 : Number(this.bisHouseSelected) - 1;
                this.addNumberToHouse(this.bisHouseSelected, this.getHouseNumber(copiedHouse), true, true);
                this.gameui.onBisChosen(this.bisHouseSelected, direction);
            },

            fillBis: function (isTentative = false) {
                var bisDiv = dojo.byId(`bis_${this.bisUsed}_score_${this.player.id}`);
                this.bisUsed = this._manageTentativeAction(bisDiv, this.bisUsed, isTentative);
            },

            /** Roundabout related functions */
            setupRoundAboutsScore: function (lowerSheetData) {
                for (var roundaboutNumber = 0; roundaboutNumber < 2; roundaboutNumber++) {
                    var roundaboutDiv = dojo.place(this.format_block('roundabout_score_div', { 'roundabout_number': roundaboutNumber, 'player_id': this.player.id }), this.sheetDiv);
                    if (!this.readonly)
                        dojo.connect(roundaboutDiv, 'onclick', this, dojo.partial(this.onRoundaboutClicked, roundaboutNumber));
                }
            },

            onRoundaboutClicked: function (roundaboutNumber, evt) {
                console.log("onRoundaboutClicked", roundaboutNumber);
                if (!(roundaboutNumber == this.roundaboutsBuilt))
                    this.showMessage("You must check the first available value (from top to bottom).", "error")

                // If already selected -> roundabout
                if (this.roundaboutHouseId == undefined) {
                    this.roundaboutPreviousState = this.gameui.clientState.getState();
                    this.gameui.clientState.setState("ROUNDABOUT_PLACEMENT");
                }
                else {
                    this._removeTentativeNumber(this.roundaboutHouseId);
                    this.roundaboutHouseId = undefined;
                    if (this.gameui.clientState.getState() == "ROUNDABOUT_PLACEMENT")
                        this.gameui.clientState.setState(this.roundaboutPreviousState);
                }
                // onClick -> setState, such that house selection works like bis, other actions show an error message, and a cancel button appears.

            },

            onRoundaboutPlaceChoosen: function (houseId) {
                console.log("YAY", houseId);
                this.roundaboutHouseId = Number(houseId);
                this.fillRoundabout(houseId, true);
                this.gameui.clientState.setState(this.roundaboutPreviousState);
            },

            fillRoundabout: function (houseId, isTentative = false) {
                var houseDiv = dojo.byId(`house_${houseId}_${this.player.id}`);
                houseDiv.textContent = "⨀";
                dojo.removeClass(houseDiv, 'empty_house');
                if (isTentative)
                    dojo.addClass(houseDiv, "tentative");
                else {
                    dojo.removeClass(houseDiv, "tentative");
                    this.roundaboutHouseId = undefined;
                    dojo.addClass(`roundabout_${this.roundaboutsBuilt}_score_${this.player.id}`, "used");
                    this.roundaboutsBuilt += 1;
                    this.fillEstateFence(houseId);
                    this.fillEstateFence(Number(houseId) - 1);
                }
            },

            /** Permit refusal related functions */
            setupPermitRefusalsScore: function (lowerSheetData) {
                for (var permitRefusalNumber = 0; permitRefusalNumber < 3; permitRefusalNumber++) {
                    dojo.place(this.format_block('permit_refusal_score_div', { 'permit_refusal_number': permitRefusalNumber, 'player_id': this.player.id }), this.sheetDiv);
                    if (lowerSheetData.permitRefusals > permitRefusalNumber)
                        this.fillPermitRefusal();
                }
            },

            fillPermitRefusal: function () {
                var permitDiv = dojo.byId(`permit_refusal_${this.permitRefusalNumber}_score_${this.player.id}`);
                dojo.addClass(permitDiv, "used");
                this.permitRefusalNumber += 1;
            },

            setupResultingScores: function (scoreData) {
                for (var category in scoreData) {
                    var scoreDiv = dojo.place(gameui.format_block('result_score_div', { 'category': category, 'player_id': this.player.id }), this.sheetDiv);
                };
                for (let tempRank = 1; tempRank <= 3; tempRank++) {
                    dojo.place(gameui.format_block('temp_ranking_score_div', { 'rank': tempRank, 'player_id': this.player.id }), this.sheetDiv);
                }
                this.fillScores(scoreData)
            },

            fillScores: function (scoreData) {
                for (var category in scoreData) {
                    var scoreDiv = dojo.byId(`result_score_${category}_${this.player.id}`);
                    scoreDiv.textContent = scoreData[category].total;
                }
                // Fill parks value in score area.
                for (var streetNumber = 0; streetNumber < 3; streetNumber++) {
                    var parkScoreDiv = dojo.byId(`park_${streetNumber}_score_${this.player.id}`);
                    parkScoreDiv.textContent = scoreData.parks[streetNumber];
                }

                // Select agency based on ranking
                for (let tempRank = 1; tempRank <= 3; tempRank++) {
                    var tempRankDiv = dojo.byId(`temp_ranking_${tempRank}_score_${this.player.id}`);
                    if (tempRank == scoreData['temps'].rank)
                        dojo.addClass(tempRankDiv, 'selected');
                    else
                        dojo.removeClass(tempRankDiv, 'selected');
                }

                // Fill real estate number below each sizes.
                for (var estateSize = 1; estateSize <= 6; estateSize++) {
                    var numberDiv = dojo.byId(`real_estate_${estateSize}_number_${this.player.id}`);
                    numberDiv.textContent = scoreData[`real_estate_${estateSize}`].number;
                }
            },

            fillUpperSheet: function (houseGameData) {
                console.log("fillUpperSheetData", houseGameData);
                houseGameData.forEach(house => {
                    if (!(house.number == null)) {
                        if (house.number == 500) {
                            this.fillRoundabout(house.house_id, false);
                        }
                        else
                            this.addNumberToHouse(house.house_id, house.number, false, house.is_bis);
                    }
                    if (house.estate_fence_on_right == 1)
                        this.fillEstateFence(house.house_id);
                    if (house.used_in_plan == 1)
                        this.fillTopFences([house.house_id]);
                });

            },

            activateHousePlacementAnimations: function (allEmpty) {
                // TODO : Case with Action Temporary worker.
                var tentativeNumbers = allEmpty ? null : this._getSelectedPossibleNumbers();
                for (var house_id in this.gameData.upper_sheets[this.player.id]) {
                    var houseDiv = dojo.byId(`house_${house_id}_${this.player.id}`);
                    for (const key in tentativeNumbers) {
                        const tentativeNumber = tentativeNumbers[key];
                        if (this.checkHouseCanBeOpened(Number(house_id), tentativeNumber))
                            dojo.addClass(houseDiv, "highlighted");
                    }
                }
                dojo.addClass(this.sheetDiv, "houses_selectable");
            },

            checkHouseCanBeOpened: function (tentativeHouseId, tentativeNumber) {
                if (!(this.getHouseNumber(tentativeHouseId) == undefined))
                    return false;
                if (this.isRoundAbout(tentativeHouseId))
                    return false;
                if (tentativeNumber == null)
                    // In this case, we simply want all Empty Houses.
                    return true;
                var tentativeHouseStreetPart = this._getHouseStreetPart(tentativeHouseId);
                for (var otherHouseId = tentativeHouseStreetPart.start; otherHouseId <= tentativeHouseStreetPart.end; otherHouseId++) {
                    // var otherHouse = this.gameData.upper_sheets[this.player.id][otherHouseId];
                    var otherHouseNumber = this.getHouseNumber(otherHouseId);
                    if (otherHouseId <= tentativeHouseId && (otherHouseNumber !== undefined) && otherHouseNumber >= tentativeNumber) {
                        return false;
                    }
                    if (otherHouseId >= tentativeHouseId && (otherHouseNumber !== undefined) && otherHouseNumber <= tentativeNumber) {
                        return false;
                    }
                }
                return true;
            },

            _getHouseStreetPart(tentativeHouseId) {
                for (var id in this.streetParts) {
                    if (this._isInStreetPart(this.roundaboutHouseId, this.streetParts[id])) {
                        var streetPartBeforeRoundabout = { 'start': this.streetParts[id].start, 'end': Number(this.roundaboutHouseId) - 1 };
                        var streetPartAfterRoundabout = { 'start': Number(this.roundaboutHouseId) + 1, 'end': this.streetParts[id].end };
                        if (this._isInStreetPart(tentativeHouseId, streetPartBeforeRoundabout))
                            return streetPartBeforeRoundabout;
                        if (this._isInStreetPart(tentativeHouseId, streetPartAfterRoundabout))
                            return streetPartAfterRoundabout;
                    } else {
                        if (this._isInStreetPart(tentativeHouseId, this.streetParts[id]))
                            return this.streetParts[id];
                    }
                }
            },

            _isInStreetPart(houseId, streetPart) {
                return ((houseId >= streetPart.start) && (houseId <= streetPart.end));
            },

            highlightAction(actionName) {
                console.log("Highlight action", actionName);
                switch (actionName) {
                    case 'Landscaper':
                        var streetId = this.placementDict[this.selectedHouse].street;
                        var parkNode = dojo.byId(`park_${streetId}_${this.parksUpgradesNumberDone[streetId]}_${this.player.id}`);
                        if (parkNode)
                            dojo.addClass(parkNode, "highlighted");
                        break;
                    case 'Surveyor':
                        this.highlightConstructibleFences();
                        break;
                    case 'Real Estate Agent':
                        for (var estateSize = 1; estateSize <= 6; estateSize++) {
                            var realEstateDiv = dojo.byId(`real_estate_${estateSize}_${this.realEstatesUpgradesNumberDone[estateSize] + 1}_score_${this.player.id}`);
                            if (realEstateDiv)
                                dojo.addClass(realEstateDiv, "highlighted");
                        }
                        break;
                    case 'Pool Manufacturer':
                        var poolNode = dojo.byId(`pool_${this.poolsBuilt}_score_${this.player.id}`);
                        if (poolNode)
                            dojo.addClass(poolNode, "highlighted");
                        break;
                    case 'Bis':
                        var bisNode = dojo.byId(`bis_${this.bisUsed}_score_${this.player.id}`);
                        if (bisNode)
                            dojo.addClass(bisNode, "highlighted");
                        break;
                }
            },

            highlightConstructibleFences() {
                var houses = this.gameData.upper_sheets[this.player.id];
                for (var house_id in houses) {
                    if (this.placementDict[house_id].avenue == 11) {
                        continue;
                    }
                    if (houses[house_id].used_in_plan == 1) {
                        // TBD : Make difference between regular and advanced plans?
                        continue;
                    }
                    dojo.addClass(`estate_fence_${house_id}_${this.player.id}`, "highlighted");
                };
            },

            removeHighlights: function () {
                dojo.removeClass(this.sheetDiv, "houses_selectable");
                dojo.query(".highlighted").forEach(function (node, index, arr) {
                    dojo.removeClass(node, "highlighted");
                });
            },
            removeHousePlacementAnimations: function () {
                dojo.removeClass(this.sheetDiv, "houses_selectable");
            },
            clearUserInput: function () {
                if (!(this.selectedHouse == undefined))
                    this._removeTentativeNumber(this.selectedHouse);
            },
            clearHouseSelection: function () {
                if (!(this.selectedHouse == undefined))
                    this._removeTentativeNumber(this.selectedHouse);
            },
            clearActionInput() {
                console.log("clearActionInput");
                this.clearBisInput();

                dojo.query(".real_estate_score_div.tentative").forEach(function (node, index, arr) {
                    dojo.removeClass(node, "tentative upgraded");
                });

                dojo.query(".pool_score_div.tentative").forEach(function (node, index, arr) {
                    dojo.removeClass(node, "tentative upgraded");
                });
                dojo.query(".house_pool_div.tentative").forEach(function (node, index, arr) {
                    dojo.removeClass(node, "tentative built");
                });

                dojo.query(".park_div.tentative").forEach(function (node, index, arr) {
                    dojo.removeClass(node, "tentative upgraded");
                });

                dojo.query(".temp_score_div.tentative").forEach(function (node, index, arr) {
                    dojo.removeClass(node, "tentative upgraded");
                });

                dojo.query(".estate_fence_div.tentative").forEach(function (node, index, arr) {
                    dojo.removeClass(node, "tentative upgraded");
                });
            },
            clearBisInput: function () {
                if (!(this.bisHouseSelected == undefined)) {
                    this._removeTentativeNumber(this.bisHouseSelected);
                    this.bisHouseSelected = undefined;
                }
                var bisDiv = dojo.byId(`bis_${this.bisUsed}_score_${this.player.id}`);
                if (bisDiv)
                    dojo.removeClass(bisDiv, "tentative upgraded");
            },
            getSelectedHouse: function () {
                return this.selectedHouse;
            },
            setAvailableHousingEstates: function (housingEstates) {
                this.availableHousingEstates = housingEstates;
            },
            highlightAvailableHousingEstates: function () {
                this.availableHousingEstates.forEach(housingEstate => {
                    for (let index = housingEstate['start']; index <= housingEstate['end']; index++) {
                        dojo.addClass(`top_fence_${index}_${this.player.id}`, "highlighted");
                    }
                });
            },
            getSelectedHousingEstates: function () {
                return this.selectedHousingEstates;
            },
            setStreetParts: function (streetParts) {
                this.streetParts = streetParts;
            },
            getHouseNumber: function (houseId) {
                var houseText = dojo.byId(`house_${houseId}_${this.player.id}`).textContent;
                if (houseText == "")
                    return undefined;
                if (houseText == "⨀")
                    return undefined;
                else
                    return Number(houseText.replace('bis', ''));
            },
            getRoundabout: function () {
                return this.roundaboutHouseId;
            },
            isRoundAbout: function (houseId) {
                var houseText = dojo.byId(`house_${houseId}_${this.player.id}`).textContent;
                if (houseText == "⨀")
                    return true;
            },

        });
    });