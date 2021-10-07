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
    g_gamethemeurl + "modules/js/Game/game.js",
    g_gamethemeurl + "modules/js/Game/modal.js",

    g_gamethemeurl + "modules/js/States/ActionsTrait.js",
    g_gamethemeurl + "modules/js/States/ConfirmWaitTrait.js",
    g_gamethemeurl + "modules/js/States/PlanValidationTrait.js",
    g_gamethemeurl + "modules/js/States/TurnTrait.js",
    g_gamethemeurl + "modules/js/States/WriteNumberTrait.js",

    g_gamethemeurl + "modules/js/wtoLayout.js",
    g_gamethemeurl + "modules/js/wtoScoreSheet.js",
    g_gamethemeurl + "modules/js/wtoConstructionCards.js",
    g_gamethemeurl + "modules/js/wtoPlanCards.js",
], function (dojo, declare) {

  return declare("bgagame.welcometo", [
    customgame.game,
    welcometo.actionsTrait,
    welcometo.confirmWaitTrait,
    welcometo.planValidationTrait,
    welcometo.turnTrait,
    welcometo.writeNumberTrait,
  ], {

    /*
     * Constructor
     */
    constructor() {
      this._isStandard = true;
      this._layoutManager = new welcometo.layout();

      this._notifications.push(
        ['updateScores', 10]
      );
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
      this.inherited(arguments);
      dojo.destroy('debug_output'); // Speedup loading page

      debug('SETUP', gamedatas);
      this._isStandard = gamedatas.options.standard;

      // Create a new div for buttons to avoid BGA auto clearing it
      dojo.place("<div id='customActions' style='display:inline-block'></div>", $("generalactions"), "after");

      // Add current turn data to highlight recent moves
      dojo.attr("game_play_area", "data-turn", gamedatas.turn);

      // Add board info to display correct scoresheet
      this._board = gamedatas.options.board;
      dojo.attr("game_play_area", "data-board", this._board);


      // Create the construction and plan cards
      this._constructionCards = new welcometo.constructionCards(gamedatas);
      this._planCards = new welcometo.planCards(gamedatas, this.player_id);

      // Setup streets icon
      var iconsElt = this.format_block("jstpl_currentPlayerBoard", {
        "horizontal" : _("Horizontal"),
        "vertical" : _("Vertical"),
        "reset" : _("Reset"),
        "cards" : parseInt(gamedatas.cardsLeft / (this._isStandard? 3 : 1)),
      });

      Object.values(gamedatas.players).forEach( player => {
        if(player.id == this.player_id){
          dojo.place(iconsElt, "player_board_" + player.id);
          return;
        }

        dojo.place(this.format_block("jstpl_playerBoard", player), "player_board_" + player.id);
        this.addTooltip("plan-status-1-" + player.id, _("Status of City Plan n°1"), "");
        this.addTooltip("plan-status-2-" + player.id, _("Status of City Plan n°2"), "");
        this.addTooltip("plan-status-3-" + player.id, _("Status of City Plan n°3"), "");
        this.addTooltip("houses-status-container-" + player.id, _("Number of houses built"), "");
        this.addTooltip("refusal-status-container-" + player.id, _("Number of permit refusals"), "");

        dojo.place(this.format_block("jstpl_spyIcon", player), "player_board_" + player.id);
        this.addTooltip("show-streets-" + player.id, '', _("Show player's scoresheet"));
        dojo.connect($("show-streets-" + player.id), "onclick", () => this.showScoreSheet(player.id) );
      });

      // Stop here if spectator
      if(this.isSpectator){
        dojo.place(iconsElt, document.querySelector(".player-board.spectator-mode"));
        dojo.query(".player-board.spectator-mode .roundedbox_main").style("display", "none");
      }

      // Icon tooltip
      this.addTooltip("show-overview", "", _("Display an overview of player's situation") );
      this.addTooltip("show-helpsheet", "", _("Display the helpsheet"));
      this.addTooltip("cards-count", _("Number of cards left in each stack"), "");
      this.addTooltip("layout-settings", _("Layout settings"), "");


      // Connect icons
      dojo.connect($("show-overview"), "onclick", () => this.showOverview() );
      dojo.connect($("show-helpsheet"), "onclick", () => this.showHelpSheet() );

      // Init the layout
      this._layoutManager.init(this._isStandard);

      // Setup the scoresheet
      this._scoreSheet = new welcometo.scoreSheet({
        gamedatas:gamedatas,
        pId:this.isSpectator? null : this.player_id,
        parentDiv:'player-score-sheet-resizable',
        slideshow:this.isSpectator,
      });

      // Update player panel counters
      this.updatePlayersData();

      g_sitecore = this;
     },

     updatePlayersData(){
       this._scoreSheet.updateScoreSheet();
       this._planCards.updateValidations();

       for(var pId in this.gamedatas.players){
         if(pId == this.player_id)
           continue;

         let player = this.gamedatas.players[pId];
         var nPermit = player.scoreSheet.scribbles.reduce((n, scribble) => n + (scribble.type == "permit-refusal"? 1 : 0), 0);
         $('permit-refusal-status-' + pId).innerHTML = nPermit;
         $('houses-built-status-' + pId).innerHTML = player.scoreSheet.houses.length;
      }
     },

     onScreenWidthChange(){
       this._layoutManager.onScreenWidthChange();
     },

     onUpdateActionButtons(){
     },

     /*
      * clearPossible:
      * 	clear every clickable space and any selected worker
      */
    clearPossible() {
      this.removeActionButtons();
      dojo.empty("customActions");
      this.onUpdateActionButtons(this.gamedatas.gamestate.name, this.gamedatas.gamestate.args);

      this._constructionCards.clearPossible();
      this._planCards.clearPossible();
      this._scoreSheet.clearPossible();
    },



     notif_updateScores(n){
       debug("Notif: updating scores", n);
       this._scoreSheet.updateScores(n.args.scores);
       this.scoreCtrl[this.player_id].toValue(n.args.scores.total);
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
       new customgame.modal("showHelpSheet", {
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
      var dial = new customgame.modal("showOverview", {
        class:"welcometo_popin",
        closeIcon:'fa-times',
        openAnimation:true,
        openAnimationTarget:"show-overview",
        contents:jstpl_overview,
      });

      this.addTooltip("overview-temp", _("The majority displayed here match only what players did until previous turn"), '');

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
          'park1' : scores['park-0'],
          'park2' : scores['park-1'],
          'park3' : scores['park-2'],
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
      let modalWidth = 1000;
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
      if(this.isSpectator){
        this._scoreSheet.slideTo(pId);
        return;
      }


      var dial = new customgame.modal("showScoreSheet", {
        class:"welcometo_popin",
        title: dojo.string.substitute( _("${player_name}'s scoresheet"), { player_name: this.gamedatas.players[pId].name}),
        closeIcon:'fa-times',
        verticalAlign:'flex-start',
      });

      new welcometo.scoreSheet({
        id:'modal',
        gamedatas:this.gamedatas,
        pId:pId,
        cId:this.player_id,
        parentDiv:'popin_showScoreSheet_contents',
        slideshow:true,
        updateTitle:true,
      });
      g_sitecore = this;

//      new welcometo.scoreSheet(this.gamedatas.players[pId], 'popin_showScoreSheet_contents');

      let box = $("ebd-body").getBoundingClientRect();
      let sheetWidth = 1544;
      let newSheetWidth = box['width']*0.5;
      let sheetScale = newSheetWidth / sheetWidth;
      dojo.style("popin_showScoreSheet_contents", "width", newSheetWidth + "px");
      dojo.query("#popin_showScoreSheet_contents .score-sheet-holder").style("transform", `scale(${sheetScale})`);
      dojo.query("#popin_showScoreSheet_contents .score-sheet-container").style("width", `${newSheetWidth}px`);
      dojo.query("#popin_showScoreSheet_contents .score-sheet-container").style("height", `${newSheetWidth}px`);

      dial.show();
    },
  });
});
