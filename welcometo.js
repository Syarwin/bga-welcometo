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
    g_gamethemeurl + "modules/js/wtoModal.js",
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


  var dockedlog_to_move_id = {};
  var customlog_to_move_id = {};
  function override_onPlaceLogOnChannel(msg) {
    debug(msg);

    // [Undocumented] Called by BGA framework on any notification message
    // Handle cancelling log messages for restart turn
    var currentLogId = this.next_log_id;
    this.inherited(override_onPlaceLogOnChannel, arguments);

    if (msg.move_id && this.next_log_id != currentLogId) {
      var moveId = +msg.move_id;
      dockedlog_to_move_id[currentLogId] = moveId;
      this.checkLogCancel(moveId);
    }

    if(msg.args.moveId){
      debug("test");
      dockedlog_to_move_id[currentLogId] = msg.args.moveId;
      this.checkLogCancel(moveId);
    }
  }

  // [Undocumented] Called by BGA framework when loading progress changes
  // Call our onLoadComplete() when fully loaded
  function override_setLoader(value, max) {
    this.inherited(override_setLoader, arguments);
    if (!this.isLoadingComplete && value >= 100) {
      this.isLoadingComplete = true;
      this.onLoadingComplete();
    }
  }


  return declare("bgagame.welcometo", ebg.core.gamegui, {
    /*
     * [Undocumented] Override BGA framework functions
     */
    setLoader: override_setLoader,
    onPlaceLogOnChannel: override_onPlaceLogOnChannel,

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
          dojo.place(this.format_block("jstpl_currentPlayerBoard", {
            "horizontal" : _("Horizontal"),
            "vertical" : _("Vertical"),
          }), "player_board_" + player.id);;

          dojo.connect($("show-overview"), "onclick", () => this.showOverview() );
          dojo.connect($("show-helpsheet"), "onclick", () => this.showHelpSheet() );
          return;
        }

        dojo.place(this.format_block("jstpl_playerBoard", player), "player_board_" + player.id);
        this.addTooltip("show-streets-" + player.id, '', _("Show player's scoresheet"));
        dojo.connect($("show-streets-" + player.id), "onclick", () => this.showScoreSheet(player.id) );
      });

      // Hack needed because player board are not ready on constructor
      this._layoutManager.init(this._isStandard);

      // Stop here if spectator
      if(this.isSpectator)
        return;

      // Setup the scoresheet
      var player = gamedatas.players[this.player_id];
      this._scoreSheet = new bgagame.wtoScoreSheet(player, 'player-score-sheet-resizable');
     },


     onLoadingComplete: function () {
       debug('Loading complete');

       // Handle previously cancelled moves
       this.cancelLogs(this.gamedatas.cancelMoveIds);
     },


     onUpdateActionButtons(){
       /*
       this.addPrimaryActionButton('btnTest2', "Test modal", () => {
       });
       */
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
        new bgagame.wtoModal("showHelpSheet", {
          autoShow:true,
          class:"welcometo_popin",
          closeIcon:'fa-times',
          openAnimation:true,
          openAnimationTarget:"show-helpsheet",
        });
      },


     /*
      * Display a table with a nice overview of current situation for everyone
      */
     showOverview(){
       debug("Showing overview:");
       var dial = new bgagame.wtoModal("showOverview", {
         class:"welcometo_popin",
         closeIcon:'fa-times',
         openAnimation:true,
         openAnimationTarget:"show-overview",
         contents:jstpl_overview,
       });


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
           'permitScore' : -scores['permit-total'],
           'permitNumber' : nPermit,
           'total' : scores['total']
         };
         dojo.place(this.format_block('jstpl_overviewRow', data), 'player-overview-body');
       }

       let box = $("ebd-body").getBoundingClientRect();
       let modalWidth = 860;
       let newModalWidth = box['width']*0.8;
       let modalScale = newModalWidth / modalWidth;
       if(modalScale > 1) modalScale = 1;
       dojo.style("popin_showOverview", "transform", `scale(${modalScale})`);

       dial.show();
     },



     /*
      * Display the scoresheet of a player
      */
     showScoreSheet(pId){
       debug("Showing scoresheet of player :", pId);

       var dial = new bgagame.wtoModal("showScoreSheet", {
         class:"welcometo_popin",
         title: dojo.string.substitute( _("${player_name}'s scoresheet"), { player_name: this.gamedatas.players[pId].name}),
         closeIcon:'fa-times',
         verticalAlign:'flex-start',
       });

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

     notif_newPrivateState(n){
       this.onLeavingState(this.gamedatas.gamestate.name);
       this.setupPrivateState(n.args.state, n.args.args);
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



     notif_updateScores(n){
       debug("Notif: updating scores", n);
       this._scoreSheet.updateScores(n.args.scores);
       this.scoreCtrl[this.player_id].toValue(n.args.scores.total);
     },


     notif_updatePlayersData(n){
       debug("Notif: updating player's data", n);
       for(var pId in n.args.players){
         this.scoreCtrl[pId].toValue(n.args.players[pId].score);
       }
       this.gamedatas.players = n.args.players;
       this._planCards.updateValidations(n.args.planValidations);
       this._scoreSheet.showLastActions(n.args.players, n.args.turn);
     },

     ///////////////////////////////
     //////   Start of turn  ///////
     ///////////////////////////////
     notif_newCards(n){
       debug("Notif: dealing new cards", n);
       this._constructionCards.newTurn(n.args.cards, n.args.turn);
       dojo.attr("game_play_area", "data-turn", n.args.turn);
     },


     // EXPERT MODE
     notif_giveCard(n){
       debug("Notif: giving card to next player", n);
       this._constructionCards.giveCard(n.args.stack, n.args.pId);
     },


     // SOLO MODE
     notif_soloCard(n){
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
         this._constructionCards.highlight(args.selectedCards, args.cancelable? this.onClickCancelTurn.bind(this) : null);
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
         this.addPrimaryActionButton("btnRoundabout", _("Build a roundabout"), () => this.takeAction("roundabout"));
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

     notif_writeNumber(n){
       debug("Notif: writing a number on a house", n);
       this._scoreSheet.addHouseNumber(n.args.house, true);
     },


     ///////////////////////////////////
     /////////   Roundabout   //////////
     ///////////////////////////////////
     onEnteringStateBuildRoundabout(args){
       this.onEnteringStateWriteNumber(args);
       this.addPassActionButton();
     },


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
     notif_addScribble(n){
       debug("Notif: scribbling a zone", n);
       this._scoreSheet.addScribble(n.args.scribble, true);
     },


     notif_addMultipleScribbles(n){
       debug("Notif: scribbling several zones", n);
       n.args.scribbles.forEach(scribble => this._scoreSheet.addScribble(scribble, true) );
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
       this.addDangerActionButton("buttonPassAction", _("Pass"), 'onClickPassAction');
     },

     onChoosePlan(planId){
       debug("You chose plan cards :", planId);
       this.takeAction("choosePlan", { plan: planId});
     },


     notif_scorePlan(n){
       debug("Notif: scoring plan", n);
       this._planCards.validateCurrentPlayerPlan(n.args.planId, n.args.validation, true);
     },



     onEnteringStateAskReshuffle(args){
       this.addPrimaryActionButton("btnReshuffle", _("Reshuffle"), () => this.takeAction('reshuffle') );
       this.addPassActionButton();
     },

     notif_reshuffle(n){
       debug("Notif: reshuffling cards", n);
       this._constructionCards.discard();
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

     notif_clearTurn(n){
       debug("Notif: restarting turn", n);
       this._scoreSheet.clearTurn(n.args.turn);
       this._planCards.clearTurn(n.args.turn);
       this.cancelLogs(n.args.moveIds);
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


     ////////////////////////////////
     /////////   Apply turn   ///////
     ////////////////////////////////
     /*
     getCasinoPlacementAnimation: function (playerId, casinoId) {
         var animationHTML = this.format_block('animation', { 'avatar_url': this.getPlayerAvatar(playerId), 'player_id': playerId, 'avenue': this.casinos_placement_dict[casinoId].avenue, 'street': this.casinos_placement_dict[casinoId].street });
         dojo.place(animationHTML, `city_${this.player_id}`);
         return dojo.fadeOut({
             node: `animation_${playerId}`,
             duration: this.animationTiming,
             delay: 0,
             easing: dojo.fx.easing.quintIn,
             beforeBegin: function (node) { dojo.removeClass(node, "hidden"); },
             onEnd: function (node) { dojo.destroy(node); }
         })
     },
*/
     // test_animation: function () {
     //     var animations = [{ 'casinoId': 13, 'playerId': 2316488 }, { 'casinoId': 27, 'playerId': 2316489 }];
     //     var toPlay = [];
     //     for (let index = 0; index < animations.length; index++) {
     //         var animation = animations[index];
     //         toPlay.push(this.getCasinoPlacementAnimation(animation.playerId, animation.casinoId));
     //     }
     //     dojo.fx.chain(toPlay).play();
     // },


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



    /*
     * cancelLogs:
     *   strikes all log messages related to the given array of move IDs
     */
    checkLogCancel(moveId){
      debug("Cancelled ids : ", this.gamedatas.cancelMoveIds)
      if (this.gamedatas.cancelMoveIds != null && this.gamedatas.cancelMoveIds.includes(moveId)) {
        this.cancelLogs([moveId]);
      }
    },

    cancelLogs (moveIds) {
      if (Array.isArray(moveIds)) {
        debug('Cancel log messages for move IDs', moveIds);
        debug(customlog_to_move_id);

        var elements = [];
        // Desktop logs
        for (var logId in this.log_to_move_id) {
          var moveId = +this.log_to_move_id[logId];
          if (moveIds.includes(moveId)) {
            elements.push($('log_' + logId));
          }
        }
        // Custom logs
        for (var logId in customlog_to_move_id) {
          var moveId = +customlog_to_move_id[logId];
          if (moveIds.includes(moveId)) {
            elements.push($('log_' + logId));
          }
        }


        // Mobile logs
        for (var logId in dockedlog_to_move_id) {
          var moveId = +dockedlog_to_move_id[logId];
          if (moveIds.includes(moveId)) {
            elements.push($('dockedlog_' + logId));
          }
        }

        debug(elements);
        // Add strikethrough
        elements.forEach(e => {
          if (e != null) {
            dojo.addClass(e, 'cancel');
          }
        });
      }
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
         ['reshuffle', 1000],
         ['giveCard', 1000],
         ['updateScores', 10],
         ['updatePlayersData', 2000],
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
