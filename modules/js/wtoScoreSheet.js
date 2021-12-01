var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

function arrayEquals(a, b) {
  return Array.isArray(a) && Array.isArray(b) && a.length === b.length && a.every((val, index) => val === b[index]);
}

define([
  'dojo',
  'dojo/_base/declare',
  'dojo/fx',
  'ebg/core/gamegui',
  g_gamethemeurl + 'modules/js/Game/modal.js',
  g_gamethemeurl + 'modules/js/vendor/hammer.min.js',
], function (dojo, declare) {
  const ROUNDABOUT = 100;
  const ICE_CREAM = 1;
  const CHRISTMAS = 2;

  return declare('welcometo.scoreSheet', ebg.core.gamegui, {
    /****************************************
     ********** Score sheet class ************
     *****************************************
     * create the layout for a scoresheet
     * handle the clicks event to ask user to select a house
     * animate the scribbles
     */
    gamedatas: {},
    id: 'main',
    pId: null,
    cId: null,
    slideshow: false,
    updateTitle: false,
    streetSizes: [10, 11, 12],
    _selectableHouses: null,

    constructor(args) {
      debug('Construction score sheet', args);
      dojo.safeMixin(this, args);

      this.pId = this.pId == null ? this.gamedatas.nextPlayerTable[0] : this.pId;

      var n = Object.keys(this.gamedatas.players).length;
      if (n == 1 || (n == 2 && this.cId != null)) this.slideshow = false;

      // Create container
      let container = this.tpl(
        'scoreSheetContainer',
        {
          id: this.id,
          slideshow: this.slideshow ? 1 : 0,
        },
        this.parentDiv,
      );

      if (this.slideshow) {
        dojo.connect(container.querySelector('.slideshow-left'), 'click', () => this.slide('left'));
        dojo.connect(container.querySelector('.slideshow-right'), 'click', () => this.slide('right'));

        var mc = new Hammer(container);
        mc.on('swipeleft swiperight', (ev) => {
          if (ev.type == 'swipeleft') this.slide('right');
          if (ev.type == 'swiperight') this.slide('left');
        });
      }

      this.createScoreSheet();
    },

    getNextPId(pId, direction) {
      let table = direction == 'right' ? this.gamedatas.nextPlayerTable : this.gamedatas.prevPlayerTable;
      return table[pId];
    },

    slide(direction) {
      let pId = this.getNextPId(this.pId, direction);
      if (pId == this.cId) pId = this.getNextPId(pId, direction);

      this.slideTo(pId, direction);
    },

    slideTo(pId, direction) {
      if (this.pId == pId) return;

      let oldPId = this.pId;
      this.pId = pId;
      this.createScoreSheet();

      let x = (direction == 'right' ? -1 : 1) * 1544;
      let anim = dojo.fx.combine([
        dojo.animateProperty({
          node: 'score-sheet-' + this.id + '-' + oldPId,
          properties: { left: { start: 0, end: x } },
          duration: 750,
        }),
        dojo.animateProperty({
          node: 'score-sheet-' + this.id + '-' + this.pId,
          properties: { left: { start: -x, end: 0 } },
          duration: 750,
        }),
      ]);
      dojo.connect(anim, 'onEnd', () => dojo.destroy('score-sheet-' + this.id + '-' + oldPId));
      anim.play();

      if (this.updateTitle) {
        setTimeout(() => {
          let title = dojo.string.substitute(_("${player_name}'s scoresheet"), {
            player_name: this.gamedatas.players[this.pId].name,
          });
          $('popin_showScoreSheet_title').innerHTML = title;
        }, 400);
      }
    },

    createScoreSheet() {
      this.tpl('scoreSheet', { id: this.id }, 'score-sheet-holder-' + this.id);
      this.container = 'score-sheet-' + this.id + '-' + this.pId;

      // Setup divs
      this.setupUpperSheet();
      this.setupLowerSheet();
      this.setupScores();

      this.updateScoreSheet();
    },

    updateScoreSheet() {
      let scoreSheet = this.gamedatas.players[this.pId].scoreSheet;

      // Update scores
      this.updateScores(scoreSheet.scores);
      // Add houses number
      scoreSheet.houses.forEach((house) => this.addHouseNumber(house));
      // Add scribbles
      scoreSheet.scribbles.forEach((scribble) => this.addScribble(scribble, false));
    },

    /*
     * Return a 2D array of the street
     */
    getBlankStreets() {
      return this.streetSizes.map((size) => {
        var street = [];
        for (let i = 0; i < size; i++) street[i] = [];
        return street;
      });
    },

    /*
     * Create an tpl using format_block and connect click event
     */
    tpl(tplName, data, container, clickCallback) {
      data.pId = this.pId;
      container = container || this.container;
      var elem = dojo.place(this.format_block('jstpl_' + tplName, data), container);
      if (clickCallback && !this.slideshow) {
        dojo.connect(elem, 'click', () => clickCallback(data));
      }
      return elem;
    },

    clickableTpl(tplName, data, clickCallback, container) {
      return this.tpl(tplName, data, container, clickCallback);
    },

    // Clear every (un)selectable classes
    clearPossible() {
      dojo.query('.house').removeClass('unselectable selectable');

      this._lastHouse = null;
      dojo.query('.pool').removeClass('selectable');

      if (this._zoneType != null) dojo.query('.' + this._zoneType).removeClass('unselectable selectable');
      this._selectableHouses = null;
      this._selectableZones = null;
      this._zoneType = null;

      dojo.query('.estate').forEach(dojo.destroy);
      this._selectedEstates = [];
      this._selectableSizes = null;
      this._callbackHouse = null;
      this._callbackZone = null;
      this._callbackEstate = null;
    },

    // Destroy elements added during turn (for restart)
    clearTurn(turn) {
      dojo.query(`.house div[data-turn="${turn}"]`).forEach((elt) => {
        dojo.removeClass(elt.parentNode, 'built');
        dojo.destroy(elt);
      });

      dojo.query(`.scribble-roundabout[data-turn="${turn}"]`).forEach((elt) => {
        dojo.removeClass(elt.parentNode, 'built');
        dojo.destroy(elt);
      });

      dojo.query(`.scribble[data-turn="${turn}"]`).forEach(dojo.destroy);
      dojo.query(`.scribble-circle[data-turn="${turn}"]`).forEach(dojo.destroy);
      dojo.query(`.scribble-line[data-turn="${turn}"]`).forEach(dojo.destroy);
      dojo.query(`.scribble-line-hor[data-turn="${turn}"]`).forEach(dojo.destroy);
      dojo.query(`.scribble-checkmark[data-turn="${turn}"]`).forEach(dojo.destroy);
      dojo.query(`.scribble-christmas[data-turn="${turn}"]`).forEach(dojo.destroy);
    },

    ////////////////////////
    ////////  Setup ////////
    ////////////////////////
    setupUpperSheet() {
      // City name
      this.tpl('cityName', this.gamedatas.players[this.pId]);

      // Streets
      var houses = [10, 11, 12];
      var parks = [3, 4, 5];

      for (var x = 0; x < 3; x++) {
        this.tpl('estateFence', { x: x, y: -1 });
        for (var y = 0; y < houses[x]; y++) {
          this.clickableTpl('house', { x: x, y: y }, this.onClickHouse.bind(this));
          this.clickableTpl('estateFence', { x: x, y: y }, this.onClickZoneFactory('estate-fence'));
          this.tpl('topFence', { x: x, y: y });

          if (this.gamedatas.options.board == ICE_CREAM) {
            this.tpl('iceTruck', { x: x, y: y });
          }
        }

        for (var y = 0; y < parks[x]; y++) this.clickableTpl('park', { x: x, y: y }, this.onClickZoneFactory('park'));
      }

      // Pools
      const pools = [
        [0, 2],
        [0, 6],
        [0, 7],
        [1, 0],
        [1, 3],
        [1, 7],
        [2, 1],
        [2, 6],
        [2, 10],
      ];
      pools.forEach((pool) => {
        this.clickableTpl('pool', { x: pool[0], y: pool[1] }, this.onClickPool.bind(this));
      });

      // IceCream expansion
      if (this.gamedatas.options.board == ICE_CREAM) {
        const iceCreams = [
          [0, 0],
          [0, 2],
          [0, 4],
          [0, 5],
          [0, 7],
          [0, 9],
          [1, 0],
          [1, 1],
          [1, 3],
          [1, 4],
          [1, 6],
          [1, 8],
          [1, 9],
          [2, 0],
          [2, 1],
          [2, 3],
          [2, 4],
          [2, 5],
          [2, 7],
          [2, 9],
          [2, 10],
        ];
        iceCreams.forEach((iceCream) => {
          this.tpl('iceCream', { x: iceCream[0], y: iceCream[1] });
        });

        this.tpl('iceTruck', { x: 3, y: 0 });
        this.tpl('iceTruck', { x: 3, y: 1 });

        this.tpl('iceCream', { x: 0, y: -1 });
        this.tpl('iceCream', { x: 1, y: -1 });
        this.tpl('iceCream', { x: 2, y: -1 });
        this.tpl('iceCream', { x: 2, y: -2 });
      }
    },

    setupLowerSheet() {
      // Pool & Bis
      for (var i = 0; i < 9; i++) {
        this.clickableTpl('scorePool', { x: i }, this.onClickZoneFactory('score-pool'));
        this.tpl('scoreBis', { x: i });
      }

      // Temp
      for (var i = 0; i < 11; i++) this.tpl('scoreTemp', { x: i });

      // Real estate
      var estates = [1, 2, 3, 4, 4, 4];
      for (var x = 0; x < 6; x++) {
        for (var y = 0; y < estates[x]; y++) {
          this.clickableTpl('scoreEstate', { x: x, y: y }, this.onClickZoneFactory('score-estate'));
        }
      }

      // Permit refusal
      for (var i = 0; i < 3; i++) {
        this.clickableTpl('permitRefusal', { x: i }, this.onClickZoneFactory('permit-refusal'));
      }

      // Roundabout
      for (var i = 0; i < 2; i++) {
        this.tpl('scoreRoundabout', { x: i });
      }
    },

    /******************************
     *******************************
     ********* TOP HALF ************
     *******************************
     ******************************/

    /////////////////////////
    //////// Houses /////////
    /////////////////////////
    /*
     * Ask the use to select a house to write a number inside
     *   numbers is an array of possible number to write with associated locations
     */
    promptNumbers(numbers, callback) {
      this._callbackHouse = callback;
      var streets = this.getBlankStreets();
      for (let number in numbers) {
        numbers[number].forEach((house) => streets[house[0]][house[1]].push(number));
      }
      this.makeHousesSelectable(streets);
    },

    /*
     * For each house, if there is at least one writtable number, make it selectable
     */
    makeHousesSelectable(streets) {
      this._selectableHouses = streets;
      dojo.query('.house').addClass('unselectable');
      streets.forEach((street, x) => {
        street.forEach((house, y) => {
          if (house.length > 0)
            dojo.query(`#${this.pId}_house_${x}_${y}`).removeClass('unselectable').addClass('selectable');
        });
      });
    },

    /*
     * Listener called when a house is clicked
     */
    onClickHouse(house) {
      // Make sure we are in a state where you can click a house and that we can write a number on the house
      if (this._selectableHouses == null || this._selectableHouses[house.x][house.y].length == 0) return;

      var numbers = this._selectableHouses[house.x][house.y];
      if (numbers.length == 1) {
        // Only one number, we can call the callback directly
        this._callbackHouse(numbers[0], house.x, house.y);
      } else {
        // Open a modal to ask the number to write
        var dial = new customgame.modal('chooseNumber', {
          class: 'welcometo_popin',
          closeIcon: 'fa-times',
          title: _('Choose the number you want to write'),
          openAnimation: true,
          openAnimationTarget: `${house.pId}_house_${house.x}_${house.y}`,
        });

        numbers.forEach((number) => {
          var div = dojo.place(
            `<div class='number-choice' data-number='${number}'></div>`,
            'popin_chooseNumber_contents',
          );
          dojo.connect(div, 'onclick', () => {
            dial.destroy();
            this._callbackHouse(number, house.x, house.y);
          });
        });
        dial.show();
      }
    },

    /*
     * Add a number to a house
     */
    addHouseNumber(house, animation) {
      var id = `${house.pId}_house_${house.x}_${house.y}`;
      if (dojo.hasClass(id, 'built')) return;

      // Advanced variant : roundabout
      if (house.number == ROUNDABOUT) {
        var div = this.tpl('scribbleRoundabout', house, id);
        dojo.addClass(id, 'built');

        if (animation) {
          playSound('welcometo_scribble');
          div.classList.add('animate');
        }
      }
      // Classic number
      else {
        house.bis = house.isBis ? '<span>b</span>' : '';
        this.tpl('houseNumber', house, id);
        dojo.addClass(id, 'built');
      }
    },

    /////////////////////////
    //////// Pools /////////
    /////////////////////////
    /*
     * Let the use click a pool
     */
    promptPool(house) {
      this._lastHouse = house;
      dojo.addClass(`${house.pId}_pool_${house.x}_${house.y}`, 'selectable');
    },

    onClickPool(house) {
      if (
        this._lastHouse == null ||
        this._lastHouse.x != house.x ||
        this._lastHouse.y != house.y ||
        this._selectableZones == null
      )
        return;

      this.onClickZoneFactory('score-pool')({ x: this._selectableZones[0][0] });
    },

    /******************************
     *******************************
     *** GENERIC SCRIBBLING ZONE ***
     *******************************
     ******************************/

    /*
     * Ask the use to select a score zone
     *   type is the type of zone (pool, estate, park)
     */
    promptZones(type, zones, callback) {
      this._callbackZone = callback;
      this._selectableZones = zones;
      this._zoneType = type;

      dojo.query('.' + type).addClass('unselectable');
      zones.forEach((zone) => {
        var selector = '.' + type;
        selector += `[data-x="${zone[0]}"]`;
        if (zone.length > 1) selector += `[data-y="${zone[1]}"]`;

        dojo.query(selector).removeClass('unselectable').addClass('selectable');
      });
    },

    /*
     * Check if a zone of given type is clickable
     */
    selectableZone(type, zone) {
      var data = [];
      if (typeof zone.x != 'undefined') data.push(zone.x);
      if (typeof zone.y != 'undefined') data.push(zone.y);

      return this._zoneType == type && this._selectableZones.some((val) => arrayEquals(val, data));
    },

    /*
     * Generic click handler for zone
     */
    onClickZoneFactory(type) {
      return (zone) => {
        if (!this.selectableZone(type, zone)) return;

        this._callbackZone(zone);
        this.clearPossible();
      };
    },

    /////////////////////////
    /////// Scribble ////////
    /////////////////////////
    addScribble(scribble, animation) {
      if ($('scribble-' + scribble.id) || scribble.pId != this.pId) return;

      var location = this.pId + '_' + scribble.type + '_' + scribble.x;
      if (scribble.y != null) location += '_' + scribble.y;

      if (scribble.type == 'christmas') {
        location = 'score-sheet-main-' + scribble.pId;
      }

      if (!$(location)) {
        console.error('Trying to add a scribble to an invalid location : ', location);
        return;
      }

      var scribbleTpl = 'scribble';
      if (scribble.type == 'pool') scribbleTpl = 'scribbleCircle';
      if (scribble.type == 'estate-fence') scribbleTpl = 'scribbleLine';
      if (scribble.type == 'top-fence') scribbleTpl = 'scribbleLineHor';

      // Ice cream expansion
      if (scribble.type == 'ice-truck' && scribble.x < 3) scribbleTpl = 'scribbleLineHor';
      if (scribble.type == 'ice-cream' && scribble.state == 1) scribbleTpl = 'scribbleCircle';

      // Chrismas expansion
      if (scribble.type == 'christmas') {
        scribbleTpl = 'scribbleChristmas';
        this.computeChristmasLightPosition(scribble);
      }

      this.tpl(scribbleTpl, scribble, location);

      if (animation) {
        playSound('welcometo_scribble');
        $('scribble-' + scribble.id).classList.add('animate');
      }
    },

    /*** Christmas light expansion **/
    computeChristmasLightPosition(scribble) {
      let leftHouse = this.pId + '_house_' + scribble.x + '_' + scribble.y;
      let x1 = dojo.style(leftHouse, 'left') + 22;
      let y1 = dojo.style(leftHouse, 'top') + 77;

      let rightHouse = this.pId + '_house_' + scribble.x + '_' + (+scribble.y + 1);
      let x2 = dojo.style(rightHouse, 'left') + 22;
      let y2 = dojo.style(rightHouse, 'top') + 77;

      scribble.left = x1;
      scribble.arcWidth = x2 - x1;
      scribble.width = scribble.arcWidth + 20;
      scribble.height = 60 + Math.abs(y2 - y1);
      scribble.top = Math.min(y1, y2);
      scribble.start = (y1 < y2 ? 0 : y1 - y2) + 10;
      scribble.end = y2 - y1;
      scribble.rotation = (y2 - y1) / 2;
    },

    /******************************
     *******************************
     *** PROMPT ESTATES FOR PLAN ***
     *******************************
     ******************************/
    promptPlayerEstates(plan, callback) {
      // Create estates
      plan.estates.forEach((estate) => {
        let leftFence = this.pId + '_estate-fence_' + estate.x + '_' + (estate.y - 1);
        let rightFence = this.pId + '_estate-fence_' + estate.x + '_' + (estate.y + estate.size - 1);
        estate.left = dojo.style(leftFence, 'left') + dojo.style(leftFence, 'width');
        estate.top = dojo.style(leftFence, 'top');
        estate.width = dojo.style(rightFence, 'left') - dojo.style(leftFence, 'left') - dojo.style(leftFence, 'width');
        estate.height = dojo.style(leftFence, 'height');

        this.clickableTpl('estate', estate, this.onClickEstate.bind(this));
      });

      // Init selection
      this._plan = plan;
      this._callbackEstate = callback;
      this._selectedEstates = [];
      this.updateSelectableEstates();
    },

    updateSelectableEstates() {
      // Compute the conditions unfulfilled yet
      var selectedSizes = this._selectedEstates.map((estate) => estate.size);
      var conditionsLeft = this._plan.conditions.filter((size) => {
        let i = selectedSizes.indexOf(size);
        if (i != -1) {
          selectedSizes.splice(i, 1);
          return false;
        } else {
          return true;
        }
      });

      // Update selectable estates
      dojo.query('.estate').removeClass('selectable');
      this._selectableSizes = conditionsLeft;
      this._selectableSizes.forEach((size) => dojo.query(`[data-size="${size}"]`).addClass('selectable'));

      dojo.destroy('cancelEstateSelect');
      dojo.destroy('confirmEstateSelect');
      if (this._selectedEstates.length > 0)
        this.addSecondaryActionButton('cancelEstateSelect', _('Undo'), () => this.onClickCancelEstates());
      if (this._selectableSizes.length == 0)
        this.addPrimaryActionButton('confirmEstateSelect', _('Confirm'), () => this.onClickConfirmEstates());
    },

    onClickEstate(estate) {
      let selectedIndex = this._selectedEstates.reduce((foundIndex, e, i) => {
        return e.x == estate.x && e.y == estate.y && e.size == estate.size ? i : foundIndex;
      }, -1);

      // If selected, unselect
      if (selectedIndex !== -1) {
        dojo
          .query(`.estate[data-size="${estate.size}"][data-x="${estate.x}"][data-y="${estate.y}"]`)
          .removeClass('selected');
        this._selectedEstates.splice(selectedIndex, 1);
        this.updateSelectableEstates();
        return;
      }

      // If not selectable
      if (!this._selectableSizes.includes(estate.size)) return;

      // Select the estate
      dojo
        .query(`.estate[data-size="${estate.size}"][data-x="${estate.x}"][data-y="${estate.y}"]`)
        .addClass('selected');
      this._selectedEstates.push({
        x: estate.x,
        y: estate.y,
        size: estate.size,
      });

      // Update other estates
      this.updateSelectableEstates();
    },

    onClickCancelEstates() {
      this._selectedEstates = [];
      dojo.query('.estate').removeClass('selected');
      this.updateSelectableEstates();
    },

    onClickConfirmEstates() {
      this._callbackEstate(this._selectedEstates);
    },

    /*
     * Add a blue/grey button if it doesn't already exists
     */
    addPrimaryActionButton(id, text, callback) {
      if (!$(id)) this.addActionButton(id, text, callback, 'customActions', false, 'blue');
    },

    addSecondaryActionButton(id, text, callback) {
      if (!$(id)) this.addActionButton(id, text, callback, 'customActions', false, 'gray');
    },

    /******************************
     *******************************
     *********** SCORES ************
     *******************************
     ******************************/
    setupScores() {
      this._counters = {};
      let ids = [
        'plan-0',
        'plan-1',
        'plan-2',
        'plan-total',
        'park-0',
        'park-1',
        'park-2',
        'park-total',
        'pool-total',
        'temp-total',
        'bis-total',
        'estate-mult-0',
        'estate-mult-1',
        'estate-mult-2',
        'estate-mult-3',
        'estate-mult-4',
        'estate-mult-5',
        'estate-total-0',
        'estate-total-1',
        'estate-total-2',
        'estate-total-3',
        'estate-total-4',
        'estate-total-5',
        'other-total',
        'total',
      ];

      if (this.gamedatas.options.board == ICE_CREAM) {
        ids.push('ice-cream-0', 'ice-cream-1', 'ice-cream-2', 'ice-cream-total');
      }

      if (this.gamedatas.options.board == CHRISTMAS) {
        ids.push('christmas-0', 'christmas-1', 'christmas-2', 'christmas-total');
      }

      ids.forEach((id) => {
        this.tpl('scoreCounter', { id: id });
        this._counters[id] = new ebg.counter();
        this._counters[id].create(this.pId + '_score_' + id);
      });
    },

    updateScores(scores) {
      debug('Updating scores', scores);

      for (var id in scores) {
        if (!this._counters[id]) continue;

        this._counters[id].toValue(parseInt(scores[id]));
      }
    },

    /******************************
     *******************************
     ********** OVERLAY ************
     *******************************
     ******************************/
    showLastActions(players, turn) {
      // Fade in the overlay
      var overlay = dojo.query('#score-sheet-' + this.id + '-' + this.pId + ' .scoresheet-overlay')[0];
      dojo.addClass(overlay, 'fadein');

      // Play sound
      playSound('welcometo_pin');

      // Add pins
      var pins = this.computePins(players, turn);
      pins.forEach(
        (pin, i) =>
          setTimeout(() => {
            this.addPin(pin);
          }),
        400 + i * 200,
      );

      // Fade out overlay
      setTimeout(() => dojo.addClass(overlay, 'fadeout'), 4000);
      setTimeout(() => dojo.removeClass(overlay, 'fadein fadeout'), 5000);
    },

    /*
     * Compute the pins depending on last actions of players
     *  a pin is : x,y, pId (first player built in this location), n (number of other players that built here)
     */
    computePins(players, turn) {
      var pins = [];
      for (var pId in players) {
        if (pId == this.pId) continue;

        players[pId].scoreSheet.houses.forEach((house) => {
          if (house.turn == turn) {
            var pin = pins.find((p) => p.x == house.x && p.y == house.y);
            // First time we get this location => add it
            if (typeof pin == 'undefined') {
              pins.push({
                x: house.x,
                y: house.y,
                pId: pId,
                n: 0,
              });
            }
            // Otherwise, increments n
            else pin.n++;
          }
        });
      }
      return pins;
    },

    addPin(pin) {
      var pinDiv = dojo.place(this.format_block('jstpl_pin', pin), 'score-sheet-' + this.id + '-' + this.pId);
      dojo.style(
        pinDiv.querySelector('.pin-avatar'),
        'background-image',
        "url('" + this.getPlayerAvatar(pin.pId) + "')",
      );

      var id = `${this.pId}_house_${pin.x}_${pin.y}`;
      dojo.style(pinDiv, 'left', dojo.style(id, 'left') - 20 + 'px');
      dojo.style(pinDiv, 'top', dojo.style(id, 'top') - 180 + 'px');
      dojo.addClass(pinDiv, 'popin');

      setTimeout(() => {
        dojo.addClass(pinDiv, 'popout');
        setTimeout(() => dojo.destroy(pinDiv), 1000);
      }, 4000);
    },

    getPlayerAvatar(pId) {
      return $('avatar_' + pId)
        ? dojo.attr('avatar_' + pId, 'src')
        : 'https://en.studio.boardgamearena.com:8083/data/avatar/noimage.png';
    },
  });
});
