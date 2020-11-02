var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define(["dojo", "dojo/_base/declare","ebg/core/gamegui",], function (dojo, declare) {
  return declare("bgagame.wtoCardManager", ebg.core.gamegui, {
    constructor(gamedatas) {
      debug("Seting up the cards");

      // Display construction cards
      gamedatas.constructionCards.forEach((stack, i) => {
        stack.forEach(card => {
          this.tpl('constructionCard', card, 'construction-cards-stack-' + i);
        });
      });
    },

    tpl(tplName, data, container){
      return dojo.place(this.format_block('jstpl_' + tplName, data), container);
    },
  });
});
