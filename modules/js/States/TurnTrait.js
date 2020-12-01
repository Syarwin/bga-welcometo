define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("welcometo.turnTrait", null, {
    constructor(){
      this._notifications.push(
        ['newCards', 1000],
        ['giveCard', 1000],
        ['soloCard', 4000]
      );
    },


    ///////////////////////////////
    //////   Start of turn  ///////
    ///////////////////////////////
    notif_newCards(n){
      debug("Notif: dealing new cards", n);
      this._constructionCards.newTurn(n.args.cards, n.args.turn);
      dojo.attr("game_play_area", "data-turn", n.args.turn);
    },


    // EXPERT MODE
    notif_giveCard(n){
      debug("Notif: giving card to next player", n);
      this._constructionCards.giveCard(n.args.stack, n.args.pId);
    },


    // SOLO MODE
    notif_soloCard(n){
      debug("Notif: the solo card has been drawn");
      var dial = new ebg.popindialog();
      dial.create('showSoloCard');
      dial.setTitle(_("Solo card was drawn"));
      dojo.query("#popin_showSoloCard_close i").removeClass("fa-times-circle ").addClass("fa-times");
      dial.show();
      setTimeout(() => dial.destroy(), 4000);
    },

    /////////////////////////////////
    /////////   End of game   ///////
    /////////////////////////////////
    onEnteringStateComputeScores(args){
      this.showOverview();
    },

  });
});
