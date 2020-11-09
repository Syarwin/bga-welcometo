var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

function arrayEquals(a, b) {
  return Array.isArray(a) &&
    Array.isArray(b) &&
    a.length === b.length &&
    a.every((val, index) => val === b[index]);
}

define(["dojo", "dojo/_base/declare","ebg/core/gamegui",], function (dojo, declare) {
  return declare("bgagame.wtoScoreSheet", ebg.core.gamegui, {
/****************************************
********** Score sheet class ************
*****************************************
 * create the layout for a scoresheet
 * handle the clicks event to ask user to select a house
 * animate the scribbles
 */

    constructor(player, gameData, parentDiv, gameui) {
      debug("Construction score sheet", player);
      this.player = player;
      this.streetSizes = [10, 11, 12];
      this._selectableHouses = null;

      // Create container
      this.tpl('scoreSheet', { turn: gameData.turn }, parentDiv);
      this.container = "score-sheet-" + player.id;

      // Setup divs
      this.setupUpperSheet();
      this.setupLowerSheet();

      // Add houses number
      gameData.houses.forEach(house => this.addHouseNumber(house) );

      // Add scribbles
      gameData.scribbles.forEach(scribble => this.addScribble(scribble, false) );
    },

    newTurn(turn){
      dojo.attr('score-sheet-' + this.player.id, 'data-turn', turn);
    },

    /*
     * Return a 2D array of the street
     */
    getBlankStreets(){
      return this.streetSizes.map(size => {
        var street = [];
        for(let i = 0; i < size; i++)
          street[i] = [];
        return street;
      });
    },

    /*
     * Create an tpl using format_block and connect click event
     */
    tpl(tplName, data, container, clickCallback){
      data.pId = this.player.id;
      container = container || this.container;
      var elem = dojo.place(this.format_block('jstpl_' + tplName, data), container);
      if(clickCallback){
        dojo.connect(elem, 'click', () => clickCallback(data));
      }
      return elem;
    },

    clickableTpl(tplName, data, clickCallback, container){
      return this.tpl(tplName, data, container, clickCallback);
    },


    // Clear every (un)selectable classes
    clearPossible(){
      dojo.query(".house").removeClass("unselectable selectable");

      this._lastHouse = null;
      dojo.query(".pool").removeClass("selectable");

      if(this._zoneType != null)
        dojo.query("." + this._zoneType).removeClass("unselectable selectable");
      this._selectableHouses = null;
      this._selectableZones = null;
      this._zoneType = null;
    },


    // Destroy elements added during turn (for restart)
    clearTurn(turn){
      dojo.query(`.house div[data-turn="${turn}"]`).forEach(elt => {
        dojo.removeClass(elt.parentNode, "built");
        dojo.destroy(elt);
      });

      dojo.query(`.scribble[data-turn="${turn}"]`).forEach(dojo.destroy);
      dojo.query(`.scribble-circle[data-turn="${turn}"]`).forEach(dojo.destroy);
    },

    ////////////////////////
    ////////  Setup ////////
    ////////////////////////
    setupUpperSheet(){
      // City name
      this.tpl("cityName", this.player);

      // Streets
      var houses = [10, 11, 12];
      var parks = [3, 4, 5];

      for(var x = 0; x < 3; x++){
        for(var y = 0; y < houses[x]; y++){
          this.clickableTpl('house', {x: x, y : y}, this.onClickHouse.bind(this));

          if(y < houses[x] - 1)
            this.clickableTpl('estateFence', {x: x, y : y}, this.onClickZoneFactory('estate-fence'));
        }

        for(var y = 0; y < parks[x]; y++)
          this.clickableTpl('park', {x: x, y : y}, this.onClickZoneFactory('park') );
      };

      // Pools
      var pools = [
        [0,2], [0,6], [0,7],
        [1,0], [1,3], [1,7],
        [2,1], [2,6], [2,10],
      ];
      pools.forEach(pool => {
        this.clickableTpl('pool', {x: pool[0], y:pool[1]}, this.onClickPool.bind(this));
      })
    },


    setupLowerSheet(){

      // Pool & Bis
      for(var i = 0; i < 9; i++){
        this.clickableTpl('scorePool', { x:i }, this.onClickZoneFactory('score-pool'));
        this.tpl('scoreBis', { x:i });
      }

      // Temp
      for(var i = 0; i < 11; i++)
        this.tpl('scoreTemp', { x:i });

      // Real estate
      var estates = [1,2,3,4,4,4];
      for(var x = 0; x < 6; x++){
        for(var y = 0; y < estates[x]; y++){
          this.clickableTpl('scoreEstate', {x:x, y:y}, this.onClickZoneFactory('score-estate') );
        }
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
    promptNumbers(numbers, callback){
      this._callback = callback;
      var streets = this.getBlankStreets();
      for(let number in numbers){
        numbers[number].forEach(house => streets[house[0]][house[1]].push(number));
      }
      this.makeHousesSelectable(streets);
    },

    /*
     * For each house, if there is at least one writtable number, make it selectable
     */
    makeHousesSelectable(streets){
      this._selectableHouses = streets;
      dojo.query(".house").addClass("unselectable");
      streets.forEach((street,x) => {
        street.forEach((house,y) => {
          if(house.length > 0)
            dojo.query(`#${this.player.id}_house_${x}_${y}`).removeClass("unselectable").addClass("selectable");
        });
      });
    },


    /*
     * Listener called when a house is clicked
     */
    onClickHouse(house){
      // Make sure we are in a state where you can click a house and that we can write a number on the house
      if(this._selectableHouses == null || this._selectableHouses[house.x][house.y].length == 0)
        return;

      var numbers = this._selectableHouses[house.x][house.y];
      if(numbers.length == 1){
        // Only one number, we can call the callback directly
        this._callback(numbers[0], house.x, house.y);
      } else {
        // Open a modal to ask the number to write
        var dial = new ebg.popindialog();
        dial.create('chooseNumber');
        dial.setTitle(_("Choose the number you want to write"));
        dojo.query("#popin_chooseNumber_close i").removeClass("fa-times-circle ").addClass("fa-times");

        numbers.forEach(number => {
          var div = dojo.place(`<div class='number-choice' data-number='${number}'></div>`, 'popin_chooseNumber_contents');
          dojo.connect(div, 'onclick', () => {
            dial.destroy();
            this._callback(number, house.x, house.y);
          });
        });
        dial.show();
      }
    },


    /*
     * Add a number to a house
     */
    addHouseNumber(house){
      var id = `${house.pId}_house_${house.x}_${house.y}`;
      house.bis = house.isBis? _("Bis") : "";
      this.tpl("houseNumber", house, id);
      dojo.addClass(id, "built");
    },



    /////////////////////////
    //////// Pools /////////
    /////////////////////////
    /*
     * Let the use click a pool
     */
    promptPool(house){
      this._lastHouse = house;
      dojo.addClass(`${house.pId}_pool_${house.x}_${house.y}`, "selectable");
    },

    onClickPool(house){
      if(this._lastHouse == null || this._lastHouse.x != house.x || this._lastHouse.y != house.y || this._selectableZones == null)
        return;

      this.onClickZoneFactory("score-pool")({x : this._selectableZones[0][0]});
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
    promptZones(type, zones, callback){
      this._callback = callback;
      this._selectableZones = zones;
      this._zoneType = type;

      dojo.query("." + type).addClass("unselectable");
      zones.forEach(zone => {
        var selector = "." + type;
        selector += `[data-x="${zone[0]}"]`;
        if(zone.length > 1)
          selector += `[data-y="${zone[1]}"]`;

        dojo.query(selector).removeClass("unselectable").addClass("selectable");
      });
    },

    /*
     * Check if a zone of given type is clickable
     */
    selectableZone(type, zone){
      var data = [];
      if(typeof zone.x != "undefined") data.push(zone.x);
      if(typeof zone.y != "undefined") data.push(zone.y);

      return this._zoneType == type && this._selectableZones.some(val => arrayEquals(val, data) );
    },

    /*
     * Generic click handler for zone
     */
     onClickZoneFactory(type){
       return (zone) => {
         if(!this.selectableZone(type, zone))
          return;

          this.clearPossible();
          this._callback(zone);
        };
     },

     /////////////////////////
     /////// Scribble ////////
     /////////////////////////
     addScribble(scribble, animation){
       var location = this.player.id + "_" + scribble.type + "_" + scribble.x;
       if(scribble.y != null)
        location += "_" + scribble.y;

       if(!$(location)){
         console.error("Trying to add a scribble to an invalid location : ", location);
         return;
       }

       var scribbleTpl = "scribble";
       if(scribble.type == "pool") scribbleTpl = "scribbleCircle";
       if(scribble.type == "estate-fence") scribbleTpl = "scribbleLine";
       this.tpl(scribbleTpl, scribble, location);
       if(animation){
         playSound("welcometo_scribble");
         $("scribble-" + scribble.id).classList.add("animate");
       }
     },

  });
});
