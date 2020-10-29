/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * welcometo implementation : © Geoffrey VOYER <geoffrey.voyer@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * welcometo.js
 *
 * welcometo user interface script
 * 
 * In this file, you are describing the logic of your user interface, in Javascript language.
 *
 */

define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    g_gamethemeurl + "modules/wtoCardViewer.js",
    g_gamethemeurl + "modules/wtoScoreSheet.js",
],
    function (dojo, declare) {
        var gameui = declare("bgagame.welcometo", ebg.core.gamegui, {
            constructor: function () {
                console.log('welcometo constructor');
                this.playerBoards = {};
                this.scoreSheets = {};

                // Here, you can init the global variables of your user interface
                // Example:
                // this.myGlobalValue = 0;

            },

            /*
                setup:
                
                This method must set up the game user interface according to current game situation specified
                in parameters.
                
                The method is called each time the game interface is displayed to a player, ie:
                _ when the game starts
                _ when a player refreshes the game page (F5)
                
                "gamedatas" argument contains all datas retrieved by your "getAllDatas" PHP method.
            */

            setup: function (gameData) {
                console.log("GameData", gameData);

                this.lastTurnNotifications = [];
                // Setting up player boards
                for (var playerId in gameData.players) {
                    var player = gameData.players[playerId];
                    this.createPlayerBoard(player);
                    // this.createScoreSheets(player, gameData);
                    this.lastTurnNotifications[playerId] = [];
                    // dojo.place(this.format_block('last_turn_content', { player_id: playerId }), $(`modal_content_${playerId}_last_turn`));
                }

                this.createCardViewer(gameData);
                this.createOwnScoreSheet(gameData);
                this.clientState = ClientState(this);

                this.initLastTurnNotifications(gameData.last_turn_logs);

                // Setup game notifications to handle (see "setupNotifications" method below)

                this.setupNotifications();

                console.log("Ending game setup");
            },

            ///////////////////////////////////////////////////
            //// Game & client states

            // onEnteringState: this method is called each time we are entering into a new game state.
            //                  You can use this method to perform some user interface changes at this moment.
            //
            onEnteringState: function (stateName, args) {
                console.log('Entering state: ' + stateName, args);

                switch (stateName) {

                    case 'validatePlans':
                        // if (this.current_player_is_active) {
                        // TBD : Highlight only for active players! BGA issue.
                        this.cardViewer.setToProjectSelectionMode();
                        this.scoreSheets[this.player_id].setAvailableHousingEstates(args.args[this.player_id]);
                        this.scoreSheets[this.player_id].highlightAvailableHousingEstates();
                        // }
                        break;
                    case 'playerTurn':
                        this.cardViewer.setToConstructionSelectionMode();
                        if (!(args.args._private.canPlay) && (this.gamedatas.options.expert))
                            this.clientState.setState("NEW_TURN_WITH_PERMIT_REFUSAL");
                        else
                            this.clientState.setState("NEW_TURN");
                        this.scoreSheets[this.player_id].setStreetParts(args.args._private.streetParts);
                        break;
                    case 'applyTurns':
                        this.emptyLastTurnNotifications();
                    case 'dummmy':
                        break;
                }
            },

            // onLeavingState: this method is called each time we are leaving a game state.
            //                 You can use this method to perform some user interface changes at this moment.
            //
            onLeavingState: function (stateName) {
                console.log('Leaving state: ' + stateName);

                switch (stateName) {

                    case 'validatePlans':
                        this.scoreSheets[this.player_id].removeHighlights();
                    /* Example:
                    
                    case 'myGameState':
                    
                        // Hide the HTML block we are displaying only during this game state
                        dojo.style( 'my_html_block_id', 'display', 'none' );
                        
                        break;
                   */


                    case 'dummmy':
                        break;
                }
            },

            // onUpdateActionButtons: in this method you can manage "action buttons" that are displayed in the
            //                        action status bar (ie: the HTML links in the status bar).
            //        
            onUpdateActionButtons: function (stateName, args) {
                console.log('onUpdateActionButtons: ' + stateName);

                if (this.isCurrentPlayerActive()) {
                    switch (stateName) {
                        case 'client_house_selected':

                            this.addActionButton('passEffect', _('Don\'t do the card effect'), 'onPassEffect');
                            break;
                        case 'validatePlans':

                            this.addActionButton('submitPlan', _('Submit this plan with those houses'), 'onSubmitPlan');
                            break;
                        case 'client_new_turn_with_permit_refusal':
                            this.addActionButton('submitPermitRefusal', _('Submit these cards and add permit refusal.'), 'onSubmitPermitRefusal');
                            break;
                    }
                }
            },

            ///////////////////////////////////////////////////
            //// Utility methods

            createPlayerBoard: function (player) {
                var playerBoard = PlayerBoard(player, this);
                this.playerBoards[player.id] = playerBoard;
                this.scoreSheets[player.id] = playerBoard.getScoreSheet();
            },

            getCurrentPlayerScoreSheet: function () {
                return this.scoreSheets[this.player_id];
            },

            createOwnScoreSheet: function (gameData) {
                var parentDiv = $('player_score_sheet_wrap');
                var player = this.gamedatas.players[this.player_id];
                var scoreSheet = new bgagame.wtoScoreSheet(player, gameData, parentDiv, this);
                this.scoreSheets[player.id] = scoreSheet;
            },

            createCardViewer: function (gameData) {
                var numberOfPlayers = Object.keys(gameData.players).length;
                var isSolo = (numberOfPlayers == 1);
                var isThreeCardMode = isSolo || gameData.options.expert;
                this.cardViewer = new bgagame.wtoCardViewer(this, isThreeCardMode);

                this.cardViewer.addToPlans(gameData.plans);
                this.cardViewer.addToConstructionStacks(gameData.stacks);
            },

            isCurrentPlayer: function (player) {
                return player.id == this.player_id;
            },

            initLastTurnNotifications: function (notifications) {
                this.emptyLastTurnNotifications();
                for (var notifId in notifications) {
                    var notif = notifications[notifId];
                    this.lastTurnNotifications[notif.args.player_id].push(notif);
                }
            },

            emptyLastTurnNotifications: function () {
                for (var playerId in this.gamedatas.players) {
                    this.lastTurnNotifications[playerId] = [];
                }
            },

            ///////////////////////////////////////////////////
            //// Player's action

            /*
            
                Here, you are defining methods to handle player's action (ex: results of mouse click on 
                game objects).
                
                Most of the time, these methods:
                _ check the action is possible at this game state.
                _ make a call to the game server
            
            */
            onSubmitPlan: function () {
                var params = {
                    project_id: this.cardViewer.getSelectedProject(),
                    house_estates: this.getCurrentPlayerScoreSheet().getSelectedHousingEstates().toString()
                };
                if (params.project_id == undefined) {
                    this.showMessage("You must click on the plan card you want to validate.", "error");
                    return;
                }
                var action = "registerPlanValidation";

                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html",
                    params,
                    this,
                    function (result) {
                    },
                    function (is_error) {
                    });
            },

            onPassEffect: function (evt) {
                var params = {
                    use_action: false
                };

                this.registerPlayerTurn(params);
            },

            registerPlayerTurn: function (specificParams) {
                this.getCurrentPlayerScoreSheet().removeHighlights();
                var generalParams = {
                    stack_number: this.cardViewer.getStackNumber(),
                    stack_action: this.cardViewer.getStackAction(),
                    house_id: this.getCurrentPlayerScoreSheet().getSelectedHouse(),
                    roundabout: this.getCurrentPlayerScoreSheet().getRoundabout(),
                };
                var params = { ...generalParams, ...specificParams };
                var action = "registerPlayerTurn";

                this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html",
                    params,
                    this,
                    function (result) {
                    },
                    function (is_error) {
                    });
            },

            onConstructionCardsSelected: function () {
                console.log("onConstructionCardsSelected");
                if (!(this.clientState.getState() == "NEW_TURN_WITH_PERMIT_REFUSAL"))
                    this.clientState.setState("CARDS_READY");
            },

            onConstructionCardsUnselected: function () {
                console.log("onConstructionCardsUnselected");
                if (!(this.clientState.getState() == "NEW_TURN_WITH_PERMIT_REFUSAL"))
                    this.clientState.setState("CARDS_NOT_READY");
            },

            onHouseSelected: function () {
                console.log("onHouseSelected");
                this.clientState.setState("HOUSE_SELECTED");
            },

            onEstateFenceSelected: function (fenceId) {
                var params = {
                    use_action: true,
                    fence_id: fenceId
                };

                this.registerPlayerTurn(params);
            },

            onRealEstateSelected: function (estateSize, upgradeNumber) {
                var params = {
                    use_action: true,
                    estate_size: estateSize
                };

                this.registerPlayerTurn(params);
            },

            onParkSelected: function (streetId, parkNumber) {
                var params = {
                    use_action: true,
                };

                this.registerPlayerTurn(params);
            },

            onPoolSelected: function (poolNumber) {
                var params = {
                    use_action: true,
                };

                this.registerPlayerTurn(params);
            },

            onTempAgencyChosen: function (delta) {
                var params = {
                    use_action: true,
                    delta: delta,
                };

                this.registerPlayerTurn(params);
            },

            onBisChosen: function (houseId, direction) {
                var params = {
                    use_action: true,
                    bis_copy_from: direction,
                    bis_house_id: houseId,
                };

                this.registerPlayerTurn(params);
            },

            onSubmitPermitRefusal: function (evt) {
                var params = {
                    house_id: 0, // Because mandatory in game action. Not used anyway.
                    use_action: false,
                    permit_refusal: true,
                }
                if ((this.cardViewer.getStackNumber() == undefined) || (this.cardViewer.getStackAction() == undefined))
                    this.showMessage("You must select the two cards you want to discard.", "error");
                else
                    this.registerPlayerTurn(params);
            },

            /* Example:

            onMyMethodToCall1: function( evt )
            {
                console.log( 'onMyMethodToCall1' );
                
                // Preventing default browser reaction
                dojo.stopEvent( evt );
    
                // Check that this action is possible (see "possibleactions" in states.inc.php)
                if( ! this.checkAction( 'myAction' ) )
                {   return; }
    
                this.ajaxcall( "/welcometo/welcometo/myAction.html", { 
                                                                        lock: true, 
                                                                        myArgument1: arg1, 
                                                                        myArgument2: arg2,
                                                                        ...
                                                                     }, 
                             this, function( result ) {
                                
                                // What to do after the server call if it succeeded
                                // (most of the time: nothing)
                                
                             }, function( is_error) {
    
                                // What to do after the server call in anyway (success or failure)
                                // (most of the time: nothing)
    
                             } );        
            },        
            
            */


            ///////////////////////////////////////////////////
            //// Reaction to cometD notifications

            /*
                setupNotifications:
                
                In this method, you associate each of your game notifications with your local method to handle it.
                
                Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" calls in
                      your welcometo.game.php file.
            
            */
            setupNotifications: function () {
                console.log('notifications subscriptions setup');

                dojo.subscribe('newCards', this, "notif_newCards");
                dojo.subscribe('houseBuilt', this, "notif_houseBuilt");
                dojo.subscribe('fenceBuilt', this, "notif_fenceBuilt");
                dojo.subscribe('realEstatePromoted', this, "notif_realEstatePromoted");
                dojo.subscribe('parkBuilt', this, "notif_parkBuilt");
                dojo.subscribe('poolBuilt', this, "notif_poolBuilt");
                dojo.subscribe('temporaryWorkerHired', this, "notif_temporaryWorkerHired");
                dojo.subscribe('bisHouseBuilt', this, "notif_bisHouseBuilt");
                dojo.subscribe('planDone', this, "notif_planDone");
                dojo.subscribe('permitRefusal', this, "notif_permitRefusal");
                dojo.subscribe('scoresUpdated', this, "notif_scoresUpdated");
                dojo.subscribe('soloCardDrawn', this, "notif_soloCardDrawn");
                dojo.subscribe('roundaboutBuilt', this, "notif_roundaboutBuilt");


                // Example 2: standard notification handling + tell the user interface to wait
                //            during 3 seconds after calling the method in order to let the players
                //            see what is happening in the game.
                // dojo.subscribe( 'cardPlayed', this, "notif_cardPlayed" );
                // this.notifqueue.setSynchronous( 'cardPlayed', 3000 );
                // 
            },

            notif_newCards: function (notif) {
                console.log('notif_newCards', notif.args);
                this.cardViewer.updateConstructionStacks(notif.args.cards);
            },

            notif_houseBuilt: function (notif) {
                console.log('notif_houseBuilt', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].addNumberToHouse(notif.args.house_id, notif.args.house_number);
            },

            notif_realEstatePromoted: function (notif) {
                console.log('notif_realEstatePromoted', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].fillRealEstate(notif.args.size);
            },

            notif_fenceBuilt: function (notif) {
                console.log('notif_fenceBuilt', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].fillEstateFence(notif.args.fence_id);
            },

            notif_parkBuilt: function (notif) {
                console.log('notif_parkBuilt', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].fillPark(notif.args.street);
            },

            notif_poolBuilt: function (notif) {
                console.log('notif_poolBuilt', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].fillPool(notif.args.house_id);
            },

            notif_temporaryWorkerHired: function (notif) {
                console.log('notif_temporaryWorkerHired', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].fillTemp();
            },

            notif_bisHouseBuilt: function (notif) {
                console.log('notif_bisHouseBuilt', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].fillBis();
                this.scoreSheets[notif.args.player_id].addNumberToHouse(notif.args.house_id, notif.args.house_number, false, true);
            },

            notif_planDone: function (notif) {
                console.log('notif_planDone', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].setPlanScore(notif.args.plan_number, notif.args.score);
                this.cardViewer.approvePlan(notif.args.plan_id);

                this.scoreSheets[notif.args.player_id].fillTopFences(notif.args.houses_used);

            },

            notif_soloCardDrawn: function (notif) {
                console.log('notif_soloCardDrawn', notif);
                this.cardViewer.approveAllPlans();
            },

            notif_permitRefusal: function (notif) {
                console.log('notif_permitRefusal', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].fillPermitRefusal();
            },

            notif_roundaboutBuilt: function (notif) {
                console.log('notif_roundaboutBuilt', notif);

                this.lastTurnNotifications[notif.args.player_id].push(notif);

                this.scoreSheets[notif.args.player_id].fillRoundabout(notif.args.roundabout_house_id);
            },

            notif_scoresUpdated: function (notif) {
                console.log('notif_scoresUpdated', notif.args.scores);
                for (var playerId in notif.args.scores) {
                    this.scoreSheets[playerId].fillScores(notif.args.scores[playerId]);
                    this.scoreCtrl[playerId].setValue(notif.args.scores[playerId].total.total);
                }
            },
        });

        var PlayerBoard = function (player, gameui) {
            var playerId = player.id;
            var node = $('player_board_' + playerId);

            var setHelperCard = function (playerId) {
                var iconClass = 'help_card_icon';
                var helper = dojo.place(gameui.format_block('jstpl_player_board', { id: playerId, icon: iconClass }), node);
                gameui.addTooltipHtml(helper.id, "<img class='help_card_tooltip'></img>");
                dojo.connect(helper, 'onclick', gameui, onHelperCardClick);
            };

            var onHelperCardClick = function () {
                var helperCardDialog = new ebg.popindialog();
                helperCardDialog.create('helperCard');
                helperCardDialog.setTitle(_("Player's helper card"));
                helperCardDialog.setContent("<img class='help_card_tooltip'></img>");
                helperCardDialog.show();
            }

            var setLastTurnModal = function (playerId) {
                var helper = dojo.place(gameui.format_block('jstpl_player_board', { id: playerId, icon: 'last_turn_icon' }), node);
                dojo.connect(helper, 'onclick', gameui, dojo.partial(onLastTurnModalClick, playerId));
            }

            var onLastTurnModalClick = function (playerId) {
                var lastTurnDialog = new ebg.popindialog();
                lastTurnDialog.create(`lastTurnModal${playerId}`);
                lastTurnDialog.setTitle(_("Here's what I did last turn"));
                var contentBlocks = "";
                for (let index = 0; index < gameui.lastTurnNotifications[playerId].length; index++) {
                    var interpolatedString = interpolate(gameui.lastTurnNotifications[playerId][index].log, gameui.lastTurnNotifications[playerId][index].args);
                    var content = `<div class="last_turn_item">${interpolatedString}</div>`;
                    contentBlocks = contentBlocks.concat(content);
                }
                lastTurnDialog.setContent(gameui.format_block('last_turn_content', { player_id: playerId, content_blocks: contentBlocks }));
                lastTurnDialog.show();
            }

            var interpolate = function (templatedString, params) {
                const names = Object.keys(params);
                const vals = Object.values(params);
                return new Function(...names, `return \`${templatedString}\`;`)(...vals);
            };

            var setScoreSheetModal = function (playerId) {
                var iconClass = 'score_sheet_icon';

                var helper = dojo.place(gameui.format_block('jstpl_player_board', { id: playerId, icon: iconClass }), node);
                var modal = dojo.place(gameui.format_block('player_board_modal', { player_id: playerId }), 'game_play_area');
                var onModalClick = function (event) {
                    console.log('onModalClick', event);
                    var myCloseModalButton = dojo.byId(`close_modal_${playerId}`);
                    if (event.target == modal || event.target == myCloseModalButton) {
                        // If we click on the close button or outside the content
                        modal.style.display = "none";
                    }
                };

                var onHelperClick = function () {
                    modal.style.display = "block";
                }

                dojo.connect(modal, 'onclick', gameui, onModalClick);
                dojo.connect(helper, 'onclick', gameui, onHelperClick);

                return new bgagame.wtoScoreSheet(player, gameui.gamedatas, dojo.byId(`modal_content_${playerId}`), gameui, true);
            }

            if (gameui.isCurrentPlayer(player)) {
                setHelperCard(playerId);
            } else {
                var scoreSheet = setScoreSheetModal(playerId);
            }
            setLastTurnModal(playerId);

            return {
                getScoreSheet: function () {
                    return scoreSheet;
                }
            }
        }

        var ClientState = function (gameui) {
            var currentState = "NEW_TURN";
            var stateList = ["NEW_TURN", "CARDS_NOT_READY", "CARDS_READY", "HOUSE_SELECTED", "BIS_SELECTED"];

            var effectDescription = function (actionName) {
                switch (actionName) {
                    case 'Surveyor':
                        return _("${you} might click on a fence between two houses to delimit housing estates.");
                    case 'Landscaper':
                        return _("${you} might click on the lowest available park value at the end of the street.");
                    case 'Real Estate Agent':
                        return _("${you} might click on the first available value from any Real Estate column.");
                    case 'Pool Manufacturer':
                        return _("${you} might click on the next number from the pool column to build a pool, if your house actually has a pool planned.");
                    case 'Temp Agency':
                        return _("${you} might select the house number you want to use in the modal.");
                    case 'Bis':
                        return _("${you} might click on the first available value from the bis score column, and then select where to duplicate a house.");
                }
            }

            var manageHighlight = function (stateId) {
                if (gameui.isSpectator)
                    return;
                gameui.getCurrentPlayerScoreSheet().removeHighlights();

                switch (stateId) {
                    case "CARDS_READY":
                        gameui.getCurrentPlayerScoreSheet().activateHousePlacementAnimations(false);
                        break;
                    case "HOUSE_SELECTED":
                        var actionName = gameui.cardViewer.getSelectedAction();
                        gameui.getCurrentPlayerScoreSheet().highlightAction(actionName);
                        break;
                    case "BIS_SELECTED":
                        gameui.getCurrentPlayerScoreSheet().activateHousePlacementAnimations(true);
                        break;
                }
            };

            return {
                setState: function (state) {
                    console.log("Old state", currentState);
                    console.log("New state", state);
                    switch (state) {

                        case "NEW_TURN":
                        case "NEW_TURN_WITH_PERMIT_REFUSAL":
                        case "CARDS_NOT_READY":
                        case "CARDS_READY":
                            gameui.getCurrentPlayerScoreSheet().clearHouseSelection();
                        case "HOUSE_SELECTED":
                            gameui.getCurrentPlayerScoreSheet().clearActionInput();
                        case "BIS_SELECTED":
                    }
                    switch (state) {
                        case "NEW_TURN":
                            if (gameui.checkPossibleActions("registerPlayerTurn")) {
                                gameui.setClientState("client_new_turn", {
                                    descriptionmyturn: _("${you} must pick a pair of construction cards."), // TBD : Get from states?
                                });
                            }
                            break;
                        case "NEW_TURN_WITH_PERMIT_REFUSAL":
                            console.log("Within");
                            if (gameui.checkPossibleActions("registerPlayerTurn")) {
                                gameui.setClientState("client_new_turn_with_permit_refusal", {
                                    descriptionmyturn: _("${you} cannot play, but should still select the two cards you want to discard."),
                                });
                            }
                            break;
                        case "CARDS_NOT_READY":
                            if (gameui.checkPossibleActions("registerPlayerTurn")) {
                                gameui.setClientState("client_cards_not_ready", {
                                    descriptionmyturn: _("${you} must pick a pair of construction cards."),
                                });
                            }
                            break;
                        case "CARDS_READY":
                            if (gameui.checkPossibleActions("registerPlayerTurn")) {
                                gameui.setClientState("client_cards_ready", {
                                    descriptionmyturn: _("${you} must choose where to place the house"),
                                });
                            }
                            break;
                        case "HOUSE_SELECTED":
                            if (gameui.checkPossibleActions("registerPlayerTurn")) {
                                gameui.setClientState("client_house_selected", {
                                    descriptionmyturn: effectDescription(gameui.cardViewer.getSelectedAction()),
                                });
                            }
                            break;
                        case "BIS_SELECTED":
                            if (gameui.checkPossibleActions("registerPlayerTurn")) {
                                gameui.setClientState("client_bis_selected", {
                                    descriptionmyturn: _("${you} must choose an empty house"),
                                });
                            }
                            break;
                        case "ROUNDABOUT_PLACEMENT":
                            if (gameui.checkPossibleActions("registerPlayerTurn")) {
                                gameui.setClientState("client_roundabout_placement", {
                                    descriptionmyturn: _("${you} must choose an empty house to build a new roundabout."),
                                });
                            }
                            break;
                    }
                    currentState = state;
                    manageHighlight(state);
                },

                stateIsBefore: function (state) {
                    return stateList.indexOf(currentState) < stateList.indexOf(state)
                },

                getState: function () {
                    return currentState;
                }
            }
        }

        return gameui;
    });
