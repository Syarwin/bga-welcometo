var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define(["dojo", "dojo/_base/declare","ebg/core/gamegui",], function (dojo, declare) {
  return declare("bgagame.wtoLayout", ebg.core.gamegui, {
/*********************************
********* Layout Manager *********
*********************************/

    constructor() {
      debug("Seting up the layout manager");

      dojo.place(`
        <div class='upperrightmenu_item' id="darkmode-switch">
          <input type="checkbox" class="checkbox" id="chk-darkmode" />
          <label class="label" for="chk-darkmode">
            <div class="ball"></div>
          </label>
        </div>
        `, 'upperrightmenu', 'first');
    },


    onScreenWidthChange(){
      dojo.style('page-content', 'zoom', '');
      dojo.style('page-title', 'zoom', '');
      dojo.style('right-side-first-part', 'zoom', '');

      let box = $('welcometo-container').getBoundingClientRect();

      let sheetWidth = 1544;
      let newSheetWidth = 0.7*box['width'];
      let sheetScale = 0.7*box['width'] / sheetWidth;
      dojo.style("player-score-sheet-resizable", "transform", `scale(${sheetScale})`);
      dojo.style("player-score-sheet", "width", `${newSheetWidth}px`);
      dojo.style("player-score-sheet", "height", `${newSheetWidth}px`);

      let cardsWidth = 433;
      let newCardsWidth = 0.2*box['width'] - 20;
      let cardsScale = newCardsWidth / cardsWidth;
      dojo.style('construction-cards-container-resizable', 'transform', `scale(${cardsScale})`);
      dojo.style('construction-cards-container', 'width', `${newCardsWidth - 10}px`);

      let plansWidth = 228;
      let newPlansWidth = 0.1*box['width'] - 10;
      let plansScale = newPlansWidth / plansWidth;
      dojo.style('plan-cards-container-resizable', 'transform', `scale(${plansScale})`);
      dojo.style('plan-cards-container', 'width', `${newPlansWidth - 20}px`);

    }
  });
});
