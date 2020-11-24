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

var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };


define([
    "dojo", "dojo/_base/declare",
    "ebg/core/gamegui",
    "ebg/counter",
    "ebg/stock",
    g_gamethemeurl + "modules/js/wtoLayout.js",
    g_gamethemeurl + "modules/js/wtoScoreSheet.js",
    g_gamethemeurl + "modules/js/wtoConstructionCards.js",
    g_gamethemeurl + "modules/js/wtoPlanCards.js",
], function (dojo, declare) {
  const AUTOMATIC = 101;
  const DISABLED = 1;
  const ENABLED = 2;

  const CONFIRM = 102;
  const CONFIRM_TIMER = 1;
  const CONFIRM_ENABLED = 2;
  const CONFIRM_DISABLED = 3;


  return declare("bgagame.welcometo", ebg.core.gamegui, {
    /*
     * Constructor
     */
    constructor() {
      this._connections = [];
      this._isStandard = true;
//      this.default_viewport = 'width=700, user-scalable=yes';
      this._layoutManager = new bgagame.wtoLayout();
    },


    /*
     * Setup:
     *  This method set up the game user interface according to current game situation specified in parameters
     *  The method is called each time the game interface is displayed to a player, ie: when the game starts and when a player refreshes the game page (F5)
     *
     * Params :
     *  - mixed gamedatas : contains all datas retrieved by the getAllDatas PHP method.
     */
    setup(gamedatas) {
dojo.destroy('debug_output'); // Speedup loading page

      debug('SETUP', gamedatas);
      this._isStandard = gamedatas.options.standard;

      // Update layout manager data
      this._layoutManager.setStackMode(this._isStandard);

      // Create a new div for buttons to avoid BGA auto clearing it
      dojo.place("<div id='customActions' style='display:inline-block'></div>", $("generalactions"), "after");

      // Add current turn data to highlight recent moves
      dojo.attr("game_play_area", "data-turn", gamedatas.turn);

      // Setup game notifications and user preference listener
      this.setupNotifications();
      this.initPreferencesObserver();

      // Create the construction and plan cards
      this._constructionCards = new bgagame.wtoConstructionCards(gamedatas);
      this._planCards = new bgagame.wtoPlanCards(gamedatas, this.player_id);

      // Setup streets icon
      Object.values(gamedatas.players).forEach( player => {
        if(player.id == this.player_id){
          dojo.place(jstpl_currentPlayerBoard, "player_board_" + player.id);;
          this._layoutManager.init(); // Hack needed because player board are not ready on constructor
          dojo.connect($("show-overview"), "onclick", () => this.showOverview() );
          dojo.connect($("show-helpsheet"), "onclick", () => this.showHelpSheet() );
          return;
        }

        dojo.place(this.format_block("jstpl_playerBoard", player), "player_board_" + player.id);
        this.addTooltip("show-streets-" + player.id, '', _("Show player's scoresheet"));
        dojo.connect($("show-streets-" + player.id), "onclick", () => this.showScoreSheet(player.id) );
      });

      // Stop here if spectator
      if(this.isSpectator)
        return;

      // Setup the scoresheet
      var player = gamedatas.players[this.player_id];
      this._scoreSheet = new bgagame.wtoScoreSheet(player, 'player-score-sheet-resizable');
     },



     ///////////////////////////////////
     ///////////////////////////////////
     /////////////  Modals /////////////
     ///////////////////////////////////
     ///////////////////////////////////

      /*
       * Dsiplay a table with a nice overview of current situation for everyone
       */
      showHelpSheet(){
        debug("Showing helpsheet:");

        // Open a modal to ask the number to write
        var dial = new ebg.popindialog();
        dial.create('showHelpSheet');
        dial.setTitle(_("Helpsheet"));
        dojo.query("#popin_showHelpSheet_close i").removeClass("fa-times-circle ").addClass("fa-times");
        dial.show();
        dojo.connect($("popin_showHelpSheet_underlay"), "click", () => dial.destroy() );
      },


     /*
      * Display a table with a nice overview of current situation for everyone
      */
     showOverview(){
       debug("Showing overview:");

       // Open a modal to ask the number to write
       var dial = new ebg.popindialog();
       dial.create('showOverview');
       dial.setTitle(_("Overview"));
       dojo.query("#popin_showOverview_close i").removeClass("fa-times-circle ").addClass("fa-times");
       dojo.place(jstpl_overview, 'popin_showOverview_contents');

       for(var pId in this.gamedatas.players){
         let player = this.gamedatas.players[pId];
         var scores = player.scoreSheet.scores;
         var nTemp = player.scoreSheet.scribbles.reduce((n, scribble) => n + (scribble.type == "score-temp"? 1 : 0), 0);
         var nPermit = player.scoreSheet.scribbles.reduce((n, scribble) => n + (scribble.type == "permit-refusal"? 1 : 0), 0);
         var data = {
           'playerName' : player.name,
           'houses' : player.scoreSheet.houses.length,
           'plan0' : scores['plan-0']? (scores['plan-0'] + '<i class="fa fa-star"></i>') : "-",
           'plan1' : scores['plan-1']? (scores['plan-1'] + '<i class="fa fa-star"></i>') : "-",
           'plan2' : scores['plan-2']? (scores['plan-2'] + '<i class="fa fa-star"></i>') : "-",
           'park' : scores['park-total'],
           'pool' : scores['pool-total'],
           'tempNumber' : nTemp,
           'tempScore' : scores['temp-total'],
           'estates' : scores['estate-total-0'] + scores['estate-total-1'] + scores['estate-total-2']
                      + scores['estate-total-3'] + scores['estate-total-4'] + scores['estate-total-5'],
           'bis' : -scores['bis-total'],
           'permitScore' : scores['permit-total'],
           'permitNumber' : nPermit,
           'total' : scores['total']
         };
         dojo.place(this.format_block('jstpl_overviewRow', data), 'player-overview-body');
       }

       dial.show();
       dojo.connect($("popin_showOverview_underlay"), "click", () => dial.destroy() );
     },



     /*
      * Display the scoresheet of a player
      */
     showScoreSheet(pId){
       debug("Showing scoresheet of player :", pId);

       // Open a modal to ask the number to write
       var dial = new ebg.popindialog();
       dial.create('showScoreSheet');
       dial.setTitle(dojo.string.substitute( _("${player_name}'s scoresheet"), { player_name: this.gamedatas.players[pId].name}) );
       dojo.query("#popin_showScoreSheet_close i").removeClass("fa-times-circle ").addClass("fa-times");
       new bgagame.wtoScoreSheet(this.gamedatas.players[pId], 'popin_showScoreSheet_contents');

       let box = $("ebd-body").getBoundingClientRect();
       let sheetWidth = 1544;
       let newSheetWidth = box['width']*0.5;
       let sheetScale = newSheetWidth / sheetWidth;
       dojo.style("popin_showScoreSheet_contents", "width", newSheetWidth + "px");
       dojo.query("#popin_showScoreSheet_contents .score-sheet").style("transform", `scale(${sheetScale})`);
       dojo.query("#popin_showScoreSheet_contents .score-sheet-container").style("width", `${newSheetWidth}px`);
       dojo.query("#popin_showScoreSheet_contents .score-sheet-container").style("height", `${newSheetWidth}px`);

       dial.show();
       dojo.connect($("popin_showScoreSheet_underlay"), "click", () => dial.destroy() );
     },

     ///////////////////////////////////////
     ////////  Game & client states ////////
     ///////////////////////////////////////

     /*
      * onEnteringState:
      * 	this method is called each time we are entering into a new game state.
      *
      * params:
      *  - str stateName : name of the state we are entering
      *  - mixed args : additional information
      */
     onEnteringState(stateName, args) {
       debug('Entering state: ' + stateName, args);

       // Private state machine
       if(args.parallel){
         this.setupPrivateState(args.args._private.state, args.args._private.args);
         return;
       }

       // Stop here if it's not the current player's turn for some states
       if (["playerAssign"].includes(stateName) && !this.isCurrentPlayerActive())
         return;

       // Call appropriate method
       var methodName = "onEnteringState" + stateName.charAt(0).toUpperCase() + stateName.slice(1);
       if (this[methodName] !== undefined)
         this[methodName](args.args);
     },

     /*
      * Private state
      */
     setupPrivateState(state, args){
       if(this.gamedatas.gamestate.parallel)
         delete this.gamedatas.gamestate.parallel;
       this.gamedatas.gamestate.name = state.name;
       this.gamedatas.gamestate.descriptionmyturn = state.descriptionmyturn;
       this.gamedatas.gamestate.possibleactions = state.possibleactions;
       this.gamedatas.gamestate.transitions = state.transitions;
       this.gamedatas.gamestate.args = args;
       this.updatePageTitle();
       this.onEnteringState(state.name, this.gamedatas.gamestate);
     },

     notif_newPrivateState(args){
       this.onLeavingState(this.gamedatas.gamestate.name);
       this.setupPrivateState(args.args.state, args.args.args);
     },

     /*
      * onLeavingState:
      * 	this method is called each time we are leaving a game state.
      *
      * params:
      *  - str stateName : name of the state we are leaving
      */
     onLeavingState(stateName) {
       debug('Leaving state: ' + stateName);
       this.clearPossible();
     },



     notif_updateScores(args){
       debug("Notif: updating scores", args);
       this._scoreSheet.updateScores(args.args.scores);
       this.scoreCtrl[this.player_id].toValue(args.args.scores.total);
     },


     notif_updatePlayersData(args){
       debug("Notif: updating player's data", args);
       for(var pId in args.args.players){
         this.scoreCtrl[pId].toValue(args.args.players[pId].score);
       }
       this.gamedatas.players = args.args.players;
       this._planCards.updateValidations(args.args.planValidations);
     },

     ///////////////////////////////
     //////   Start of turn  ///////
     ///////////////////////////////
     notif_newCards(args){
       debug("Notif: dealing new cards", args);
       this._constructionCards.newTurn(args.args.cards, args.args.turn);
       dojo.attr("game_play_area", "data-turn", args.args.turn);
     },


     // EXPERT MODE
     notif_giveCard(args){
       debug("Notif: giving card to next player", args);
       this._constructionCards.giveCard(args.args.stack, args.args.pId);
     },


     // SOLO MODE
     notif_soloCard(args){
       debug("Notif: the solo card has been drawn");
       var dial = new ebg.popindialog();
       dial.create('showSoloCard');
       dial.setTitle(_("Solo card was drawn"));
       dojo.query("#popin_showSoloCard_close i").removeClass("fa-times-circle ").addClass("fa-times");
       dial.show();
       setTimeout(() => dial.destroy(), 4000);
     },

     /////////////////////////////////////
     //////   Display basic info   ///////
     /////////////////////////////////////
     displayBasicInfo(args){
       // Add an UNDO button if there is something to cancel
       if(args.cancelable && !$('buttonCancelTurn')){
         this.addSecondaryActionButton('buttonCancelTurn', _('Restart turn'), 'onClickCancelTurn');
       }

       if(args.selectedCards){
         this._constructionCards.highlight(args.selectedCards);
       }

       if(args.selectedPlans && args.selectedPlans.length > 0){
         this._planCards.highlight(args.selectedPlans);
       }
     },


     ////////////////////////////////////////////
     //////   Choose construction cards   ///////
     ////////////////////////////////////////////
     onEnteringStateChooseCards(args){
       this.displayBasicInfo(args);
       this._constructionCards.promptPlayer(args.selectableStacks, this.onChooseCards.bind(this));

       if(args.selectableStacks.length == 0){
         // Permit refusal
         this.gamedatas.gamestate.descriptionmyturn = _("You cannot write a number and must take a permit refusal");
         this.updatePageTitle();

         let callback = (zone) => this.takeAction("permitRefusal");
         this._scoreSheet.promptZones("permit-refusal", args.zones, callback);
         this.addDangerActionButton("btnPermitRefusal", _("Permit refusal"), callback);
       }

       if(args.canBuildRoundabout){
         this.addPrimaryActionButton("btnPermitRefusal", _("Build a roundabout"), () => this.takeAction("roundabout"));
       }
     },

     onChooseCards(choice){
       debug("You chose construction cards :", choice);
       if(this._isStandard){
         this.takeAction("chooseStack", { stack: choice});
       } else {
         this.takeAction("chooseStacks", { numberStack: choice[0], actionStack: choice[1] });
       }
     },



     ////////////////////////////////////////////
     ///////   Draw a number on a house   ///////
     ////////////////////////////////////////////
     onEnteringStateWriteNumber(args){
       this.displayBasicInfo(args);
       this._scoreSheet.promptNumbers(args.numbers, this.onChooseNumber.bind(this));
     },

     onChooseNumber(number, x, y){
       debug("You chose to write", number, " at location ", x, y);
       this.takeAction("writeNumber", { number: number, x:x, y:y});
       this.clearPossible();
     },

     notif_writeNumber(args){
       debug("Notif: writing a number on a house", args);
       this._scoreSheet.addHouseNumber(args.args.house, true);
     },


     ///////////////////////////////////
     /////////   Roundabout   //////////
     ///////////////////////////////////
     onEnteringStateBuildRoundabout(args){
       this.onEnteringStateWriteNumber(args);
       this.addPassActionButton();
     },

/*
     notif_writeNumber(args){
       debug("Notif: writing a number on a house", args);
       this._scoreSheet.addHouseNumber(args.args.house);
     },
*/

     ////////////////////////////////////////////
     ////////////////////////////////////////////
     ////////   Non-automatic actions   /////////
     ////////////////////////////////////////////
     ////////////////////////////////////////////
     addPassActionButton(){
       this.addPrimaryActionButton("buttonPassAction", _("Pass"), 'onClickPassAction');
     },

     onClickPassAction(){
       this.takeAction("passAction");
     },


    //////////////////////////////////////
    ////////   Generic zones   ///////////
    //////////////////////////////////////
    /*
     * Generic handling of most zones : estate score, pool, parks, ...
     */
    promptZones(type, args, automatic){
      this.displayBasicInfo(args);

      // Automatic some action with user preference
      if(automatic && args.zones.length == 1 && this.prefs[AUTOMATIC].value == ENABLED){
        this.singleZoneSelect(args.zones)
        return;
      }

      this.addPassActionButton();
      this._scoreSheet.promptZones(type, args.zones,  (zone) => {
        this.takeAction('scribbleZone', zone);
      });
    },

    singleZoneSelect(zones){
      var zone = { x : zones[0][0] };
      if(zones[0].length == 2)
        zone.y = zones[0][1];

      this._scoreSheet.clearPossible();
      this.takeAction('scribbleZone', zone);
    },


     // Estate
     onEnteringStateActionSurveyor(args){
       this.promptZones("estate-fence", args);
     },

     // Estate
     onEnteringStateActionEstate(args){
       this.promptZones("score-estate", args);
     },

     // Parks
     onEnteringStateActionPark(args){
       this.promptZones("park", args, true);
       this.addPrimaryActionButton("btnBuildPark", _("Build park"), () => this.singleZoneSelect(args.zones));
     },

     // Pools
     onEnteringStateActionPool(args){
       this._scoreSheet.promptPool(args.lastHouse);
       this.promptZones("score-pool", args, true);
       this.addPrimaryActionButton("btnBuildPool", _("Build pool"), () => this.singleZoneSelect(args.zones));
     },


     /*
      * Add a scribble to a zone
      */
     notif_addScribble(args){
       debug("Notif: scribbling a zone", args);
       this._scoreSheet.addScribble(args.args.scribble, true);
     },


     notif_addMultipleScribbles(args){
       debug("Notif: scribbling several zones", args);
       args.args.scribbles.forEach(scribble => this._scoreSheet.addScribble(scribble, true) );
     },


     //////////////////////////////////////
     //////////   Bis action   ////////////
     //////////////////////////////////////
     onEnteringStateActionBis(args){
       this.displayBasicInfo(args);
       this.addPassActionButton();
       this._scoreSheet.promptNumbers(args.numbers, this.onChooseNumberBis.bind(this));
     },

     onChooseNumberBis(number, x, y){
       debug("You chose to write", number, " bis at location ", x, y);
       this.takeAction("writeNumberBis", { number: number, x:x, y:y});
     },


     ///////////////////////////////////////////////////
     //////   Choose city plans for validation   ///////
     //////////////////////////////////////////////////
     onEnteringStateChoosePlan(args){
       this.displayBasicInfo(args);
       this._planCards.promptPlayer(args.selectablePlans, this.onChoosePlan.bind(this));
       this.addPassActionButton();
     },

     onChoosePlan(planId){
       debug("You chose plan cards :", planId);
       this.takeAction("choosePlan", { plan: planId});
     },


     notif_scorePlan(args){
       debug("Notif: scoring plan", args);
       this._planCards.validateCurrentPlayerPlan(args.args.planId, args.args.validation, true);
     },

     ///////////////////////////////////////////////////
     //////   Choose estates to validate plan   ///////
     //////////////////////////////////////////////////
     onEnteringStateValidatePlan(args){
       this.displayBasicInfo(args);
       this._scoreSheet.promptPlayerEstates(args.currentPlan, this.onChooseEstates.bind(this));
     },

     onChooseEstates(estates){
       debug("You chose following estates for the plan :", estates);
       this.takeAction("validatePlan", { planArg : JSON.stringify(estates) });
     },


     ///////////////////////////////////////
     ///////////////////////////////////////
     /////////   Confirm/undo turn   ///////
     ///////////////////////////////////////
     ///////////////////////////////////////
     onEnteringStateConfirmTurn(args){
       this.displayBasicInfo(args);
       this.addPrimaryActionButton("buttonConfirmAction", _("Confirm"), 'onClickConfirmTurn');
       this.startActionTimer('buttonConfirmAction');
     },

     onClickConfirmTurn(){
       this.takeAction("confirmTurn");
     },

     onClickCancelTurn(){
       this.takeAction("cancelTurn");
     },

     notif_clearTurn(args){
       debug("Notif: restarting turn", args);
       this._scoreSheet.clearTurn(args.args.turn);
       this._planCards.clearTurn(args.args.turn);
     },


     onEnteringStateWaitOthers(args){
       this.displayBasicInfo(args);
     },


     startActionTimer(buttonId) {
       var button = $(buttonId);
       var isReadOnly = this.isReadOnly();
       var prefValue = (this.prefs[CONFIRM] || {}).value;
       if (button == null || isReadOnly || prefValue == CONFIRM_ENABLED) {
         debug('Ignoring startActionTimer(' + buttonId + ')', 'readOnly=' + isReadOnly, 'prefValue=' + prefValue);
         return;
       }

       // If confirm disabled, click on button
       if (prefValue == CONFIRM_DISABLED) {
         button.click();
         return;
       }

       this.actionTimerLabel = button.innerHTML;
       this.actionTimerSeconds = 10;
       this.actionTimerFunction = () => {
         var button = $(buttonId);
         if (button == null) {
           this.stopActionTimer();
         } else if (this.actionTimerSeconds-- > 1) {
           debug('Timer ' + buttonId + ' has ' + this.actionTimerSeconds + ' seconds left');
           button.innerHTML = this.actionTimerLabel + ' (' + this.actionTimerSeconds + ')';
         } else {
           debug('Timer ' + buttonId + ' execute');
           button.click();
         }
       };
       this.actionTimerFunction();
       this.actionTimerId = window.setInterval(this.actionTimerFunction, 1000);
       debug('Timer #' + this.actionTimerId + ' ' + buttonId + ' start');
     },

     stopActionTimer() {
       if (this.actionTimerId != null) {
         debug('Timer #' + this.actionTimerId + ' stop');
         window.clearInterval(this.actionTimerId);
         delete this.actionTimerId;
       }
     },


     /////////////////////////////////
     /////////   End of game   ///////
     /////////////////////////////////
     onEnteringStateComputeScores(args){
       this.showOverview();
     },


     ////////////////////////////////////////////
     ////////////////////////////////////////////
     //////////////   Utils   ///////////////////
     ////////////////////////////////////////////
     ////////////////////////////////////////////
     isReadOnly() {
       return this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
     },

     /*
 		 * Make an AJAX call with automatic lock
 		 */
     takeAction(action, data, callback) {
       data = data || {};
       data.lock = true;
       callback = callback || function (res) { };
       this.ajaxcall("/welcometo/welcometo/" + action + ".html", data, this, callback);
     },


     /*
      * Custom method to connect to easily disconnect all the connectors if needed
      */
     connect(node, action, callback){
       this._connections.push(dojo.connect(node, action, callback));
     },

     /*
      * clearPossible:
      * 	clear every clickable space and any selected worker
      */
     clearPossible() {
       this.removeActionButtons();
       dojo.empty("customActions");
       this.onUpdateActionButtons(this.gamedatas.gamestate.name, this.gamedatas.gamestate.args);

       this._connections.forEach(dojo.disconnect);
       this._connections = [];

       this._constructionCards.clearPossible();
       this._planCards.clearPossible();
       this._scoreSheet.clearPossible();
     },


     /*
      * Play a given sound that should be first added in the tpl file
      */
     playSound(sound, playNextMoveSound = true) {
       playSound(sound);
       playNextMoveSound && this.disableNextMoveSound();
     },


     /*
      * Add a blue/grey button if it doesn't already exists
      */
     addPrimaryActionButton(id, text, callback){
       if(!$(id))
        this.addActionButton(id, text, callback, "customActions", false, 'blue');
     },

     addSecondaryActionButton(id, text, callback){
       if(!$(id))
        this.addActionButton(id, text, callback, "customActions", false, 'gray');
     },

     addDangerActionButton(id, text, callback){
       if(!$(id))
        this.addActionButton(id, text, callback, "customActions", false, 'red');
     },


     onScreenWidthChange () {
       this._layoutManager.onScreenWidthChange();
     },


     /*
      * Preference polyfill
      */
     setPreferenceValue(number, newValue) {
 			var optionSel = 'option[value="' + newValue + '"]'
 			dojo.query('#preference_control_' + number + ' > ' +	optionSel
         +	', #preference_fontrol_' +number + ' > ' +	optionSel)
 				.attr('selected', true)
 			var select = $('preference_control_' + number)
 			if (dojo.isIE) {
 				select.fireEvent('onchange')
 			} else {
 				var event = document.createEvent('HTMLEvents')
 				event.initEvent('change', false, true)
 				select.dispatchEvent(event)
 			}
 		},

 		initPreferencesObserver() {
 			dojo.query('.preference_control').on(
 				'change', (e) => {
 					var match = e.target.id.match(/^preference_control_(\d+)$/)
 					if (!match) {
 						return
 					}
 					var pref = match[1]
 					var newValue = e.target.value
 					this.prefs[pref].value = newValue
 					this.onPreferenceChange(pref, newValue)
 			})
 		},

    onPreferenceChange(pref, newValue){

    },

     ///////////////////////////////////////////////////
     //////   Reaction to cometD notifications   ///////
     ///////////////////////////////////////////////////

     /*
      * setupNotifications:
      *  In this method, you associate each of your game notifications with your local method to handle it.
      *	Note: game notification names correspond to "notifyAllPlayers" and "notifyPlayer" in the santorini.game.php file.
      */
     setupNotifications() {
       var notifs = [
         ['newPrivateState', 1],
         ['clearTurn', 1],
         ['writeNumber', 1000],
         ['addScribble', 1000],
         ['addMultipleScribbles', 1000],
         ['newCards', 1000],
         ['giveCard', 1000],
         ['updateScores', 10],
         ['updatePlayersData', 10],
         ['scorePlan', 1000],
         ['soloCard', 4000],
       ];

       notifs.forEach(notif => {
         var functionName = "notif_" + notif[0];

         dojo.subscribe(notif[0], this, functionName);
         this.notifqueue.setSynchronous(notif[0], notif[1]);

         // xxxInstant notification runs same function without delay
         dojo.subscribe(notif[0] + 'Instant', this, functionName);
         this.notifqueue.setSynchronous(notif[0] + 'Instant', 10);
       });
     }
   });
});
