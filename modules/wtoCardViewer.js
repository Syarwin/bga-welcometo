define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",], function (dojo, declare) {
        return declare("bgagame.wtoCardViewer", ebg.core.gamegui, {
            constructor: function (gameui, isThreeCardMode) {
                console.log("HI from constructor wtoCardViewer");
                this.gameui = gameui;
                this.isThreeCardMode = isThreeCardMode;
                this.planStock = this.buildPlanStock();
                this.columnStocks = this.buildColumnStocks();
            },

            cardwidth: 105,
            cardheight: 140,
            planNumber: 28,
            constructionCardsNumber: 81,
            tooltipsPlans: [
                "Id shall start at one",
                _("To fulfill this City Plan, you must complete 6 housing estates made up of 1 house."),
                _("To fulfill this City Plan, you must complete 4 housing estates made up of 2 houses."),
                _("To fulfill this City Plan, you must complete 3 housing estates made up of 3 houses."),
                _("To fulfill this City Plan, you must complete 2 housing estates made up of 4 houses."),
                _("To fulfill this City Plan, you must complete 2 housing estates made up of 5 houses."),
                _("To fulfill this City Plan, you must complete 2 housing estates made up of 6 houses."),

                _("To fulfill this City Plan, you must complete 3 housing estates made up of 1 house, and a housing estate made up of 6 houses."),
                _("To fulfill this City Plan, you must complete 2 housing estates made up of 2 houses, and a housing estate made up of 5 houses."),
                _("To fulfill this City Plan, you must complete 2 housing estates made up of 3 houses, and a housing estate made up of 4 houses."),
                _("To fulfill this City Plan, you must complete a housing estate made up of 3 houses, and a housing estate made up of 6 houses."),
                _("To fulfill this City Plan, you must complete a housing estate made up of 4 houses, and a housing estate made up of 5 houses."),
                _("To fulfill this City Plan, you must complete 3 housing estates made up of 1 house, and a housing estate made up of 4 houses."),

                _("To fulfill this City Plan, you must complete 3 housing estates, one of 1 house, one of 2 houses, and one of 6 houses."),
                _("To fulfill this City Plan, you must complete 3 housing estates, one of 1 house, one of 4 houses, and one of 5 houses."),
                _("To fulfill this City Plan, you must complete a housing estate made up of 3 houses, and a housing estate made up of 4 houses."),
                _("To fulfill this City Plan, you must complete a housing estate made up of 2 houses, and a housing estate made up of 5 houses."),
                _("To fulfill this City Plan, you must complete 4 housing estates, one of 1 house, two of 2 houses, and one of 3 houses."),
                _("To fulfill this City Plan, you must complete 3 housing estates, one of 2 houses, one of 3 houses, and one of 5 houses."),

                _("To fulfill this City Plan, two streets must have all the parks built."),
                _("To fulfill this City Plan, all houses must be built on the third street."),
                _("To fulfill this City Plan, the player must build all of the parks, all of the pools, and one roundabout in the same street."),
                _("To fulfill this City Plan, all houses must be built on the first street."),
                _("To fulfill this City Plan, 5 duplicate houses’ numers (bis) must be built on the same street."),
                _("To fulfill this City Plan, two streets must have all the pools built."),
                _("To fulfill this City Plan, 7 temps must be hired."),
                _("To fulfill this City Plan, all of the parks AND all of the pools on the third street must be built."),
                _("To fulfill this City Plan, the first and last house of each street must be built."),
                _("To fulfill this City Plan, all of the parks AND all of the pools on the second street must be built."),
            ],
            cardNumberMapping: [
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
            ],
            cardActionMapping: [
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
            ],
            // stackHouseNumber;
            // stackAction;
            // _stacks;


            onStackSelect: function (stack_id, control_name, item_id) {
                if (item_id === undefined) {
                    // Not a user click, ignoring, probably changes triggered by setPlayerState.
                    return;
                }

                if (this.isThreeCardMode) {
                    this.onStackSelectSoloPlayer(stack_id, control_name, item_id);
                } else {
                    this.onStackSelectMultiPlayer(stack_id, control_name, item_id);
                }
            },

            onStackSelectMultiPlayer: function (stack_id, control_name, item_id) {
                if (this.columnStocks[stack_id].isSelected(item_id)) {
                    this.selectStack(stack_id);
                    this.gameui.onConstructionCardsSelected();
                } else {
                    this.selectStack(undefined);
                    this.gameui.onConstructionCardsUnselected();
                }
            },

            selectStack: function (stackId) {
                this.columnStocks.forEach(function (columnStock, columnId) {
                    if (columnId == stackId) {
                        columnStock.getAllItems().forEach(function (stockItem) {
                            columnStock.selectItem(stockItem.id);
                        });
                    }
                    else {
                        columnStock.unselectAll();
                    }
                }.bind(this));
                this.stackHouseNumber = stackId;
                this.stackAction = stackId;
            },

            onStackSelectSoloPlayer: function (stack_id, control_name, item_id) {
                if (!this.columnStocks[stack_id].isSelected(item_id)) {
                    this.gameui.onConstructionCardsUnselected();
                    this.stackHouseNumber = undefined;
                    this.stackAction = undefined;
                } else {
                    var newlySelectedItem = this.columnStocks[stack_id].getItemById(item_id);
                    for (var stack in this.columnStocks) {
                        var selected_items = this.columnStocks[stack].getSelectedItems();
                        selected_items.forEach(function (item) {
                            // console.log("Debug", item, stack_id, stack, item_id);
                            if ((stack == stack_id) && !(newlySelectedItem.id == item.id)) {
                                this.columnStocks[stack].unselectItem(item.id);
                            }
                            else if (!(stack == stack_id) && this._isSameKindOfCards(newlySelectedItem.type, item.type)) {
                                this.columnStocks[stack].unselectItem(item.id);
                            } else if (!(stack == stack_id) && !this._isSameKindOfCards(newlySelectedItem.type, item.type)) {
                                var selectedStacks = {};
                                if (item.type <= this.constructionCardsNumber) {
                                    this.stackHouseNumber = stack;
                                    this.stackAction = stack_id;
                                } else {
                                    this.stackHouseNumber = stack_id;
                                    this.stackAction = stack;
                                }
                                this.gameui.onConstructionCardsSelected();
                            }
                        }.bind(this));
                    }

                    var remaining_items = 0;
                    for (var stack in this.columnStocks) {
                        remaining_items += this.columnStocks[stack].getSelectedItems().length;
                    }
                    if (remaining_items < 2) {
                        this.gameui.onConstructionCardsUnselected();
                        this.stackHouseNumber = undefined;
                        this.stackAction = undefined;
                    }
                }
            },

            _isSameKindOfCards(type1, type2) {
                if ((type1 <= 81) && (type2 <= 81))
                    return true;
                if ((type1 > 81) && (type2 > 81))
                    return true;
                return false;
            },

            buildPlanStock: function () {
                var planStock = new ebg.stock();
                planStock.create(this.gameui, $('plan_cards_wrap'), this.cardwidth, this.cardheight);
                planStock.image_items_per_row = 2 * this.planNumber;
                planStock.setSelectionMode(0);
                planStock.onItemCreate = this.addPlanTooltip.bind(this);
                for (var planId = 1; planId <= this.planNumber; planId++) {
                    planStock.addItemType(planId, planId, g_gamethemeurl + 'img/plans_sprite.png', planId - 1);
                    planStock.addItemType(planId + this.planNumber, planId, g_gamethemeurl + 'img/plans_sprite.png', planId - 1 + this.planNumber);
                }
                planStock.centerItems = true;
                return planStock;
            },

            addPlanTooltip: function (card_div, card_type_id, card_id) {
                this.addTooltip(card_div.id, this.tooltipsPlans[this._getProjectType(card_type_id)], '');
            },

            _getProjectType: function (cardType) {
                if (cardType > this.planNumber)
                    return cardType - this.planNumber;
                return cardType;
            },

            buildColumnStocks: function () {
                var columnStocks = [];
                for (var column = 0; column < 3; column++) {
                    var columnStock = new ebg.stock();
                    columnStock.create(this.gameui, $('construction_cards_wrap_' + column), this.cardwidth, this.cardheight);
                    columnStock.image_items_per_row = this.constructionCardsNumber;
                    columnStock.centerItems = true;
                    for (var construction_card_id = 1; construction_card_id <= 81; construction_card_id++) {
                        columnStock.addItemType(construction_card_id, construction_card_id, g_gamethemeurl + 'img/construction_number_sprite.png', construction_card_id - 1);
                        columnStock.addItemType(construction_card_id + 81, construction_card_id + 81, g_gamethemeurl + 'img/construction_actions_sprite.png', construction_card_id - 1);
                    }
                    dojo.connect(columnStock, 'onChangeSelection', this, dojo.partial(this.onStackSelect, column));
                    columnStocks[column] = columnStock;
                }
                return columnStocks;
            },

            addToPlans: function (plans) {
                plans.forEach(plan => {
                    if (plan.approved)
                        this.planStock.addToStock(Number(plan.id) + this.planNumber);
                    else
                        this.planStock.addToStock(Number(plan.id));
                    console.log("Adding to stock");
                });
                return;
            },

            approvePlan: function (planId) {
                if (this.planStock.removeFromStock(planId))
                    this.planStock.addToStock(Number(planId) + this.planNumber);
            },

            approveAllPlans: function () {
                for (let planId = 1; planId <= this.planNumber; planId++) {
                    this.approvePlan(planId);
                }
            },

            updateConstructionStacks: function (newCards) {
                for (var stackId in newCards) {
                    this.columnStocks[stackId].removeAll();
                }
                if (this.isThreeCardMode) {
                    var newStacks = []
                    for (var stack in newCards) {
                        newStacks[stack] = [newCards[stack]];
                    }
                    this.addToConstructionStacks(newStacks);
                } else {
                    var newStacks = []
                    for (var stack in newCards) {
                        var newStack = []
                        this.stacks[stack].forEach(stackCard => {
                            if (!stackCard.flipped) {
                                stackCard.flipped = true;
                                newStack.push(stackCard);
                            }
                        });
                        newStack.push(newCards[stack]);
                        newStacks[stack] = newStack;
                    }
                    this.addToConstructionStacks(newStacks);
                }
            },

            addToConstructionStacks: function (stacks) {
                this.stacks = stacks;
                stacks.forEach((stackCards, stackId) => {
                    stackCards.forEach(stackCard => {
                        if (stackCard.flipped)
                            this.columnStocks[stackId].addToStock(Number(stackCard.id) + this.constructionCardsNumber);
                        else
                            this.columnStocks[stackId].addToStock(Number(stackCard.id));
                        if (this.isThreeCardMode)
                            this.columnStocks[stackId].addToStock(Number(stackCard.id) + this.constructionCardsNumber);
                    });
                });
            },

            getStackNumber: function () {
                return this.stackHouseNumber;
            },

            getSelectedCardNumber: function () {
                var selectedCard = this.stacks[this.stackHouseNumber].find(element => !element.flipped);
                return this.cardNumberMapping[selectedCard.id];
            },

            getStackAction: function () {
                return this.stackAction;
            },

            getSelectedAction: function () {
                if (this.isThreeCardMode)
                    var selectedCard = this.stacks[this.stackAction][0];
                else
                    var selectedCard = this.stacks[this.stackAction].find(element => element.flipped);
                return this.cardActionMapping[selectedCard.id];
            },

            clearSelection: function () {
                this.selectStack(undefined);
            },

            setToProjectSelectionMode: function () {
                this.planStock.setSelectionMode(1);
                this.columnStocks[0].setSelectionMode(0);
                this.columnStocks[1].setSelectionMode(0);
                this.columnStocks[2].setSelectionMode(0);
            },

            setToConstructionSelectionMode: function () {
                this.planStock.setSelectionMode(0);
                this.columnStocks[0].setSelectionMode(2);
                this.columnStocks[1].setSelectionMode(2);
                this.columnStocks[2].setSelectionMode(2);
            },

            getSelectedProject: function () {
                var selectedPlanCard = this.planStock.getSelectedItems()[0];
                if (selectedPlanCard == undefined)
                    return;
                return this._getProjectType(selectedPlanCard['type']);

            }
        });
    });
