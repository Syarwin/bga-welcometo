var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

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
      this.tpl('scoreSheet', {}, parentDiv);
      this.container = "score-sheet-" + player.id;

      // Setup divs
      this.setupUpperSheet();
      this.setupLowerSheet();

      // Add houses number
      gameData.houses.forEach(house => this.addHouseNumber(house) );
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
      this._selectableHouses = null;
    },


    // Destroy elements added during turn (for restart)
    clearTurn(turn){
      dojo.query(`.house div[data-turn="${turn}"]`).forEach(elt => {
        dojo.removeClass(elt.parentNode, "built");
        dojo.destroy(elt);
      });
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
        for(var y = 0; y < houses[x]; y++)
          this.clickableTpl('house', {x: x, y : y}, this.onClickHouse.bind(this));

        for(var y = 0; y < parks[x]; y++)
          this.tpl('park', {x: x, y : y});
      };
    },


    setupLowerSheet(){

      // Pool & Bis
      for(var i = 0; i < 9; i++){
        this.tpl('scorePool', { x:i });
        this.tpl('scoreBis', { x:i });
      }

      // Temp
      for(var i = 0; i < 11; i++)
        this.tpl('scoreTemp', { x:i });

      // Real estate
      var estates = [1,2,3,4,4,4];
      for(var x = 0; x < 6; x++){
        for(var y = 0; y < estates[x]; y++){
          this.tpl('scoreEstate', {x:x, y:y});
        }
      }
    },

    /////////////////////////
    /////// Scribble ////////
    /////////////////////////
    addScribble(scribble, animation){
      var location = this.player.id + "_" + scribble.location;
      if(!$(location)){
        console.error("Trying to add a scribble to an invalid location : ", location);
        return;
      }

      this.tpl("scribble", scribble, location);
      if(animation){
        $("scribble-" + scribble.id).classList.add("animate");
      }
    },


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
        // TODO : dialog
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
  });
});
