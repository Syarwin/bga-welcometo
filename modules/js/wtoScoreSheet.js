var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define(["dojo", "dojo/_base/declare","ebg/core/gamegui",], function (dojo, declare) {
  return declare("bgagame.wtoScoreSheet", ebg.core.gamegui, {
    constructor(player, gameData, parentDiv, gameui) {
      debug("Construction score sheet", player);
      this.player = player;

      // Create container
      this.tpl('scoreSheet', {}, parentDiv);
      this.container = "score-sheet-" + player.id;

      // Setup divs
      this.setupUpperSheet();
      this.setupLowerSheet();
    },

    tpl(tplName, data, container){
      data.pId = this.player.id;
      container = container || this.container;
      return dojo.place(this.format_block('jstpl_' + tplName, data), container);
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
          this.tpl('house', {x: x, y : y});

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
  });
});
