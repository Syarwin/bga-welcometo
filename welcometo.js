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
    g_gamethemeurl + "modules/js/wtoScoreSheet.js",
    g_gamethemeurl + "modules/js/wtoCardManager.js",
], function (dojo, declare) {
  return declare("bgagame.welcometo", ebg.core.gamegui, {
    /*
     * Constructor
     */
    constructor() {
      this._connections = [];
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

      // Setup game notifications
      this.setupNotifications();

      this._cardManager = new bgagame.wtoCardManager(gamedatas);

      // Stop here if spectator
      if(this.isSpectator)
        return;

      var player = gamedatas.players[this.player_id];
      this._scoreSheet = new bgagame.wtoScoreSheet(player, gamedatas, 'player-score-sheet', this);
      this._scoreSheet.addScribble({
        id :1,
        location: "score_temp_0",
        turn: 1,
      }, true);
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
         this.setupPrivateState(args);
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

     setupPrivateState(args){
       var data = args.args._private.state;
       delete this.gamedatas.gamestate.parallel;
       this.gamedatas.gamestate.name = data.name;
       this.gamedatas.gamestate.descriptionmyturn = data.descriptionmyturn;
       this.gamedatas.gamestate.possibleactions = data.possibleactions;
       this.gamedatas.gamestate.transitions = data.transitions;
       this.gamedatas.gamestate.args = args.args._private.args;
       this.updatePageTitle();
       this.onEnteringState(data.name, this.gamedatas.gamestate);
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

       if (!this.isCurrentPlayerActive())
         return;
     },


     /*
 		 * TODO description
 		 */
     takeAction(action, data, callback) {
       data = data || {};
       data.lock = true;
       callback = callback || function (res) { };
       this.ajaxcall("/mutantcrops/mutantcrops/" + action + ".html", data, this, callback);
     },



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
     },

     //     lightsOn: 'mrjack_lightsOn',

     playSound(sound, playNextMoveSound = true) {
       playSound(sound);
       playNextMoveSound && this.disableNextMoveSound();
     },


     /////////////////////////////////////
     //////   Display basic info   ///////
     /////////////////////////////////////
     displayBasicInfo(args){
       // Add an UNDO button if there is something to cancel
       if(args.cancelable){
         this.addActionButton('buttonCancel', _('Undo'), 'onClickUndo', null, false, 'gray');
       }

       // Hightlight scribbles/action from current turn
       // dojo.query(...) args.turn...
     },

     onClickUndo(){
        debug("Undo ! :)");
     },

     ////////////////////////////////////////////
     ////////////////////////////////////////////
     //////   Choose construction cards   ///////
     ////////////////////////////////////////////
     ////////////////////////////////////////////
     onEnteringStateChooseCards(args){
       debug("Test", args);
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
       var notifs = [];
       /*
         ['farmerAssigned', 1000],
         ['newField', 10],
         ['addResources', 1000],
         ['addMultiResources', 1000],
         ['sowCrop', 1500],
         ['newCrop', 100],
       ];*/

       notifs.forEach(notif => {
         dojo.subscribe(notif[0], this, "notif_" + notif[0]);
         this.notifqueue.setSynchronous(notif[0], notif[1]);
       });
     }
   });
});
