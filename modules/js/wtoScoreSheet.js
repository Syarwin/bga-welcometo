var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define([
  "dojo", "dojo/_base/declare",
  "ebg/core/gamegui",], function (dojo, declare) {
    return declare("bgagame.wtoScoreSheet", ebg.core.gamegui, {
      constructor(player, gameData, parentDiv, gameui, readonly = false) {
        this.pId = player.id;

        // Create container
        dojo.place(this.format_block('jstpl_scoreSheet', {pId : player.id}), parentDiv);
        this.container = "score-sheet-" + this.pId;

        // Setup divs
        this.setupHouses();
      },

      setupHouses(gameData) {
        // Streets
        [10, 11, 12].forEach((street, index) => {
          for(var i = 0; i < street; i++){
            dojo.place(this.format_block('jstpl_house', {x: index, y : i, pId: this.pId}), this.container);

//            if (!this.readonly)
//                dojo.connect(house_div, 'onclick', this, dojo.partial(this.onHouseSelected, house_id));
          }
        })
      },
  });
});
