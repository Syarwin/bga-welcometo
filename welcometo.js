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
var debug = isDebug ? console.info.bind(window.console) : function () {};

define([
  'dojo',
  'dojo/_base/declare',
  'ebg/core/gamegui',
  'ebg/counter',
  g_gamethemeurl + 'modules/js/Game/game.js',
  g_gamethemeurl + 'modules/js/Game/modal.js',

  g_gamethemeurl + 'modules/js/States/ActionsTrait.js',
  g_gamethemeurl + 'modules/js/States/ConfirmWaitTrait.js',
  g_gamethemeurl + 'modules/js/States/PlanValidationTrait.js',
  g_gamethemeurl + 'modules/js/States/TurnTrait.js',
  g_gamethemeurl + 'modules/js/States/WriteNumberTrait.js',

  g_gamethemeurl + 'modules/js/wtoLayout.js',
  g_gamethemeurl + 'modules/js/wtoScoreSheet.js',
  g_gamethemeurl + 'modules/js/wtoConstructionCards.js',
  g_gamethemeurl + 'modules/js/wtoPlanCards.js',
], function (dojo, declare) {
  const ICE_CREAM = 1;
  const CHRISTMAS = 2;
  const EASTER = 3;

  return declare(
    'bgagame.welcometo',
    [
      customgame.game,
      welcometo.actionsTrait,
      welcometo.confirmWaitTrait,
      welcometo.planValidationTrait,
      welcometo.turnTrait,
      welcometo.writeNumberTrait,
    ],
    {
      /*
       * Constructor
       */
      constructor() {
        this._isStandard = true;
        this._layoutManager = new welcometo.layout();

        this._notifications.push(['updateScores', 10]);
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
        dojo.place("<div id='customActions' style='display:inline-block'></div>", $('generalactions'), 'after');

        // Add current turn data to highlight recent moves
        dojo.attr('game_play_area', 'data-turn', gamedatas.turn);

        // Add board info to display correct scoresheet
        this._board = gamedatas.options.board;
        dojo.attr('ebd-body', 'data-board', this._board);

        // Create the construction and plan cards
        this._constructionCards = new welcometo.constructionCards(gamedatas);
        this._planCards = new welcometo.planCards(gamedatas, this.player_id);

        // Setup streets icon
        var iconsElt = this.format_block('jstpl_currentPlayerBoard', {
          horizontal: _('Horizontal'),
          vertical: _('Vertical'),
          reset: _('Reset'),
          cards: parseInt(gamedatas.cardsLeft / (this._isStandard ? 3 : 1)),
        });

        Object.values(gamedatas.players).forEach((player) => {
          if (player.id == this.player_id) {
            dojo.place(iconsElt, 'player_board_' + player.id);
            return;
          }

          dojo.place(this.format_block('jstpl_playerBoard', player), 'player_board_' + player.id);
          this.addTooltip('plan-status-1-' + player.id, _('Status of City Plan n°1'), '');
          this.addTooltip('plan-status-2-' + player.id, _('Status of City Plan n°2'), '');
          this.addTooltip('plan-status-3-' + player.id, _('Status of City Plan n°3'), '');
          this.addTooltip('houses-status-container-' + player.id, _('Number of houses built'), '');
          this.addTooltip('refusal-status-container-' + player.id, _('Number of permit refusals'), '');

          dojo.place(this.format_block('jstpl_spyIcon', player), 'player_board_' + player.id);
          this.addTooltip('show-streets-' + player.id, '', _("Show player's scoresheet"));
          dojo.connect($('show-streets-' + player.id), 'onclick', () => this.showScoreSheet(player.id));
        });

        // Stop here if spectator
        if (this.isSpectator) {
          dojo.place(iconsElt, document.querySelector('.player-board.spectator-mode'));
          dojo.query('.player-board.spectator-mode .roundedbox_main').style('display', 'none');
        }

        // Icon tooltip
        this.addTooltip('show-overview', '', _("Display an overview of player's situation"));
        this.addTooltip('show-helpsheet', '', _('Display the helpsheet'));
        this.addTooltip('cards-count', _('Number of cards left in each stack'), '');
        this.addTooltip('layout-settings', _('Layout settings'), '');

        // Connect icons
        dojo.connect($('show-overview'), 'onclick', () => this.showOverview());
        dojo.connect($('show-helpsheet'), 'onclick', () => this.showHelpSheet());

        // Init the layout
        this._layoutManager.init(this._isStandard);

        // Setup the scoresheet
        this._scoreSheet = new welcometo.scoreSheet({
          gamedatas: gamedatas,
          pId: this.isSpectator ? null : this.player_id,
          parentDiv: 'player-score-sheet-resizable',
          slideshow: this.isSpectator,
        });

        // Update player panel counters
        this.updatePlayersData();

        g_sitecore = this;
      },

      updatePlayersData() {
        this._scoreSheet.updateScoreSheet();
        this._planCards.updateValidations();

        for (var pId in this.gamedatas.players) {
          if (pId == this.player_id) continue;

          let player = this.gamedatas.players[pId];
          var nPermit = player.scoreSheet.scribbles.reduce(
            (n, scribble) => n + (scribble.type == 'permit-refusal' ? 1 : 0),
            0,
          );
          $('permit-refusal-status-' + pId).innerHTML = nPermit;
          $('houses-built-status-' + pId).innerHTML = player.scoreSheet.houses.length;
        }
      },

      onScreenWidthChange() {
        this._layoutManager.onScreenWidthChange();
      },

      onUpdateActionButtons() {},

      /*
       * clearPossible:
       * 	clear every clickable space and any selected worker
       */
      clearPossible() {
        this.removeActionButtons();
        dojo.empty('customActions');
        this.onUpdateActionButtons(this.gamedatas.gamestate.name, this.gamedatas.gamestate.args);

        this._constructionCards.clearPossible();
        this._planCards.clearPossible();
        this._scoreSheet.clearPossible();
      },

      notif_updateScores(n) {
        debug('Notif: updating scores', n);
        this._scoreSheet.updateScores(n.args.scores);
        this.scoreCtrl[this.player_id].toValue(n.args.scores.total);
      },

      /////////////////////////////////////
      //////   Display basic info   ///////
      /////////////////////////////////////
      displayBasicInfo(args) {
        // Add an UNDO button if there is something to cancel
        if (args.cancelable && !$('buttonCancelTurn')) {
          this.addSecondaryActionButton('buttonCancelTurn', _('Restart turn'), 'onClickCancelTurn');
        }

        if (args.selectedCards) {
          this._constructionCards.highlight(
            args.selectedCards,
            args.cancelable ? this.onClickCancelTurn.bind(this) : null,
          );
        }

        if (args.selectedPlans && args.selectedPlans.length > 0) {
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
      showHelpSheet() {
        debug('Showing helpsheet:');
        new customgame.modal('showHelpSheet', {
          autoShow: true,
          class: 'welcometo_popin',
          closeIcon: 'fa-times',
          openAnimation: true,
          openAnimationTarget: 'show-helpsheet',
        });
      },

      /*
       * Display a table with a nice overview of current situation for everyone
       */
      showOverview() {
        debug('Showing overview:');
        let width = 1000;
        let board = this.gamedatas.options.board;
        if (board == ICE_CREAM) {
          width = 1250;
        }

        var dial = new customgame.modal('showOverview', {
          class: 'welcometo_popin',
          closeIcon: 'fa-times',
          openAnimation: true,
          openAnimationTarget: 'show-overview',
          contents: this.tplOverview(),
          breakpoint: 0.9 * width,
          scale: 0.8,
        });

        this.addTooltip(
          'overview-temp',
          _('The majority displayed here match only what players did until previous turn'),
          '',
        );

        for (var pId in this.gamedatas.players) {
          let player = this.gamedatas.players[pId];
          dojo.place(this.tplOverviewRow(player), 'player-overview-body');
        }

        dial.show();
      },

      tplOverview() {
        let additional = '';
        let board = this.gamedatas.options.board;
        if (board == ICE_CREAM) {
          additional = '<th id="overview-ice-cream" colspan="3"><div></div></th>';
        } else if (board == CHRISTMAS) {
          additional = '<th id="overview-christmas" colspan="3"><div></div></th>';
        } else if (board == EASTER) {
          additional = '<th id="overview-easter"><div></div></th>';
        }

        return `
      <table id='players-overview'>
        <thead>
          <tr>
            <th id="overview-user"><i class="fa fa-user"></i></th>
            <th id="overview-houses"><div></div></th>
            <th id="overview-plan-1">n°1</th>
            <th id="overview-plan-2">n°2</th>
            <th id="overview-plan-3">n°3</th>
            ${additional}
            <th id="overview-park" colspan="3"><div></div></th>
            <th id="overview-pool"><div></div></th>
            <th id="overview-temp"><div>*</div></th>
            <th id="overview-estates"><div></div></th>
            <th id="overview-bis"><div></div></th>
            <th id="overview-other"><div></div></th>
            <th id="overview-total"><i class="fa fa-star"></i></th>
          </tr>
        </thead>
        <tbody id="player-overview-body"></tbody>
      </table>
      `;
      },

      tplOverviewRow(player) {
        let scores = player.scoreSheet.scores;
        let nTemp = player.scoreSheet.scribbles.reduce((n, scribble) => n + (scribble.type == 'score-temp' ? 1 : 0), 0);
        let nEggs = player.scoreSheet.scribbles.reduce(
          (n, scribble) => n + (scribble.type == 'egg' ? parseInt(scribble.state) : 0),
          0,
        );
        let nPermit = player.scoreSheet.scribbles.reduce(
          (n, scribble) => n + (scribble.type == 'permit-refusal' ? 1 : 0),
          0,
        );
        let houses = player.scoreSheet.houses.length;

        // Plans
        let plan0 = scores['plan-0'] ? scores['plan-0'] + '<i class="fa fa-star"></i>' : '-';
        let plan1 = scores['plan-1'] ? scores['plan-1'] + '<i class="fa fa-star"></i>' : '-';
        let plan2 = scores['plan-2'] ? scores['plan-2'] + '<i class="fa fa-star"></i>' : '-';

        // Estates
        let estates =
          scores['estate-total-0'] +
          scores['estate-total-1'] +
          scores['estate-total-2'] +
          scores['estate-total-3'] +
          scores['estate-total-4'] +
          scores['estate-total-5'];

        // IceCream expansion
        let additional = '';
        if (this.gamedatas.options.board == ICE_CREAM) {
          additional = `
        <td>${scores['ice-cream-0']}<i class="fa fa-star"></i></td>
        <td>${scores['ice-cream-1']}<i class="fa fa-star"></i></td>
        <td>${scores['ice-cream-2']}<i class="fa fa-star"></i></td>
        `;
        }

        // Christmas expansion
        if (this.gamedatas.options.board == CHRISTMAS) {
          additional = `
        <td>${scores['christmas-0']}<i class="fa fa-star"></i></td>
        <td>${scores['christmas-1']}<i class="fa fa-star"></i></td>
        <td>${scores['christmas-2']}<i class="fa fa-star"></i></td>
        `;
        }

        // Easter expansion
        if (this.gamedatas.options.board == EASTER) {
          additional = `
          <td class="overview-egg">
            <div>${nEggs}
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><!--! Font Awesome Pro 6.1.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M192 16c-106 0-192 182-192 288c0 106 85.1 192 192 192c105.1 0 192-85.1 192-192C384 198 297.1 16 192 16zM160.1 138C128.6 177.1 96 249.8 96 304C96 312.8 88.84 320 80 320S64 312.8 64 304c0-63.56 36.7-143.3 71.22-186c5.562-6.906 15.64-7.969 22.5-2.406C164.6 121.1 165.7 131.2 160.1 138z"/></svg>
            </div>
            <div>${scores['easter-egg-total']}<i class="fa fa-star"></i></div>
          </td>
        `;
        }

        return `
        <tr>
          <td>${player.name}</td>
          <td class="overview-house"><span>${houses}</span> / <span>33</span></td>
          <td>${plan0}</td>
          <td>${plan1}</td>
          <td>${plan2}</td>
          ${additional}
          <td>${scores['park-0']}<i class="fa fa-star"></i></td>
          <td>${scores['park-1']}<i class="fa fa-star"></i></td>
          <td>${scores['park-2']}<i class="fa fa-star"></i></td>
          <td>${scores['pool-total']}<i class="fa fa-star"></i></td>
          <td class="overview-temp">
            <div>${nTemp}
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52.378898 45.777344">
                <path style="fill:#000000;fill-opacity:1;stroke:none;stroke-width:0;stroke-linecap:butt;stroke-linejoin:miter;stroke-miterlimit:4;stroke-dasharray:none;stroke-opacity:1"
                   d="m 11.43164,0 -1.0625,5.216797 -10.04297,0.0957 0.19336,7.822266 H 8.53515 L 7.375,18.658201 H 0 l 0.27148,7.648437 h 5.60157 l -3.2793,18.710937 5.45703,0.759766 3.86328,-19.314453 28.29688,0.193359 3.76757,19.121094 5.31055,-0.964844 -3.57227,-18.447265 6.66211,-0.0957 V 18.640625 H 44.26758 L 43.01172,13.039063 H 52.3789 V 5.119141 l -10.62304,0.09766 -0.96485,-4.830078 -5.50586,0.869141 0.86914,3.863281 -20.47265,0.09766 0.77148,-4.154297 z m 26.17187,12.845704 1.0625,5.214843 -25.30273,0.289063 1.1582,-5.408203 z"
                   id="path817"
                   inkscape:connector-curvature="0" />
              </svg>
            </div>
            <div>${scores['temp-total']}<i class="fa fa-star"></i></div>
          </td>
          <td>${estates}<i class="fa fa-star"></i></td>
          <td>${-scores['bis-total']}<i class="fa fa-star"></i></td>
          <td>${-scores['permit-total']}<i class="fa fa-star"></i> (${nPermit}) </td>
          <td>${scores['total']}</td>
        </tr>
      `;
      },

      /*
       * Display the scoresheet of a player
       */
      showScoreSheet(pId) {
        debug('Showing scoresheet of player :', pId);
        if (this.isSpectator) {
          this._scoreSheet.slideTo(pId);
          return;
        }

        var dial = new customgame.modal('showScoreSheet', {
          class: 'welcometo_popin',
          title: dojo.string.substitute(_("${player_name}'s scoresheet"), {
            player_name: this.gamedatas.players[pId].name,
          }),
          closeIcon: 'fa-times',
          verticalAlign: 'flex-start',
        });

        new welcometo.scoreSheet({
          id: 'modal',
          gamedatas: this.gamedatas,
          pId: pId,
          cId: this.player_id,
          parentDiv: 'popin_showScoreSheet_contents',
          slideshow: true,
          updateTitle: true,
        });
        g_sitecore = this;

        //      new welcometo.scoreSheet(this.gamedatas.players[pId], 'popin_showScoreSheet_contents');

        let box = $('ebd-body').getBoundingClientRect();
        let sheetWidth = 1544;
        let newSheetWidth = box['width'] * 0.5;
        let sheetScale = newSheetWidth / sheetWidth;
        dojo.style('popin_showScoreSheet_contents', 'width', newSheetWidth + 'px');
        dojo.query('#popin_showScoreSheet_contents .score-sheet-holder').style('transform', `scale(${sheetScale})`);
        dojo.query('#popin_showScoreSheet_contents .score-sheet-container').style('width', `${newSheetWidth}px`);
        dojo.query('#popin_showScoreSheet_contents .score-sheet-container').style('height', `${newSheetWidth}px`);

        dial.show();
      },
    },
  );
});
