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
    g_gamethemeurl + "modules/js/nouislider.min.js",
    g_gamethemeurl + "modules/js/wtoLayout.js",
    g_gamethemeurl + "modules/js/wtoScoreSheet.js",
    g_gamethemeurl + "modules/js/wtoConstructionCards.js",
    g_gamethemeurl + "modules/js/wtoPlanCards.js",
], function (dojo, declare) {
  return declare("bgagame.welcometo", ebg.core.gamegui, {
    /*
     * Constructor
     */
    constructor() {
      this._connections = [];
      this._isStandard = true;
      this.default_viewport = 'width=900, user-scalable=yes';
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
      debug('SETUP', gamedatas);
      this._isStandard = gamedatas.options.standard;

      // Setup game notifications
      this.setupNotifications();

      this._constructionCards = new bgagame.wtoConstructionCards(gamedatas);
      this._planCards = new bgagame.wtoPlanCards(gamedatas);

      // Stop here if spectator
      if(this.isSpectator)
        return;

      var player = gamedatas.players[this.player_id];
      this._scoreSheet = new bgagame.wtoScoreSheet(player, gamedatas, 'player-score-sheet-resizable', this);
      /*
      this._scoreSheet.addScribble({
        id :1,
        location: "score_temp_0",
        turn: 1,
      }, true);
      */
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



     /*
      * onUpdateActionButtons:
      * 	called by BGA framework before onEnteringState
      *  in this method you can manage "action buttons" that are displayed in the action status bar (ie: the HTML links in the status bar).
      */
     onUpdateActionButtons(stateName, args) {
       debug('Update action buttons: ' + stateName, args); // Make sure it the player's turn
//TODO handle when someone do something => that remove button
       if (!this.isCurrentPlayerActive())
         return;
     },




     ///////////////////////////////
     //////   Start of turn  ///////
     ///////////////////////////////
     notif_newCards(args){
       debug("Notif: dealing new cards", args);
       this._constructionCards.newTurn(args.args.cards, args.args.turn);
       this._scoreSheet.newTurn(args.args.turn);
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

       // Hightlight scribbles/action from current turn
       // dojo.query(...) args.turn...
     },


     ////////////////////////////////////////////
     //////   Choose construction cards   ///////
     ////////////////////////////////////////////
     onEnteringStateChooseCards(args){
       this.displayBasicInfo(args);
       this._constructionCards.promptPlayer(args.selectableStacks, this.onChooseCards.bind(this));
     },

     onChooseCards(choice){
       debug("You chose construction cards :", choice);
       if(this._isStandard){
         this.takeAction("chooseStack", { stack: choice});
       } else {
         this.takeAction("chooseStacks", { number: choice[0], action: choice[1] });
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
       this._scoreSheet.addHouseNumber(args.args.house);
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
     promptZones(type, args){
       this.displayBasicInfo(args);
       this.addPassActionButton();
       this._scoreSheet.promptZones(type, args.zones, (zone) => {
         this.takeAction('scribbleZone', zone);
       });
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
       this.promptZones("park", args);
     },

     // Pools
     onEnteringStateActionPool(args){
       this.promptZones("score-pool", args);
       this._scoreSheet.promptPool(args.lastHouse);
     },


     /*
      * Add a scribble to a zone
      */
     notif_addScribble(args){
       debug("Notif: scribbling a zone", args);
       this._scoreSheet.addScribble(args.args.scribble, true);
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


     ///////////////////////////////////////
     ///////////////////////////////////////
     /////////   Confirm/undo turn   ///////
     ///////////////////////////////////////
     ///////////////////////////////////////
     onEnteringStateConfirmTurn(args){
       this.displayBasicInfo(args);
       this.addPrimaryActionButton("buttonConfirmAction", _("Confirm"), 'onClickConfirmTurn');
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
     },


     onEnteringStateWaitOthers(args){
       this.displayBasicInfo(args);
     },

     ////////////////////////////////////////////
     ////////////////////////////////////////////
     //////////////   Utils   ///////////////////
     ////////////////////////////////////////////
     ////////////////////////////////////////////
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
       this.onUpdateActionButtons(this.gamedatas.gamestate.name, this.gamedatas.gamestate.args);

       this._connections.forEach(dojo.disconnect);
       this._connections = [];

       this._constructionCards.clearPossible();
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
        this.addActionButton(id, text, callback, null, false, 'blue');
     },

     addSecondaryActionButton(id, text, callback){
       if(!$(id))
        this.addActionButton(id, text, callback, null, false, 'gray');
     },


     onScreenWidthChange () {
       this._layoutManager.onScreenWidthChange();
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
         ['newCards', 1000],
       ];

       notifs.forEach(notif => {
         dojo.subscribe(notif[0], this, "notif_" + notif[0]);
         this.notifqueue.setSynchronous(notif[0], notif[1]);
       });
     }
   });
});
