var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define(["dojo", "dojo/_base/declare","ebg/core/gamegui",], function (dojo, declare) {
  return declare("bgagame.wtoPlanCards", ebg.core.gamegui, {
/****************************************
********* City Plan cards class *********
*****************************************
 * create the layout for the cards
 */

    constructor(gamedatas) {
      debug("Seting up the plan cards");
      this._selectablePlans = [];

      // Display the cards
      gamedatas.planCards.forEach(plan => {
        dojo.place(this.format_block('jstpl_planCard', plan), 'plan-cards-container-resizable');
        dojo.connect($('plan-card-' + plan.id), 'click', () => this.onClickPlan(plan.id));
      });
    },

    // Clear everything
    clearPossible(){
      this._callback = null;
      this._selectablePlans = null;
      dojo.query(".plan-card-holder").removeClass("unselectable selectable selected");
    },

    // Hightlight selected plan
    highlight(plans){
      dojo.query(".plan-card-holder").addClass("unselectable");
      plans.forEach(planId => dojo.addClass('plan-card-' + planId, 'selected') );
    },

    ////////////////////////////////////
    ////////  Selecting a stack ////////
    ////////////////////////////////////
    promptPlayer(planIds, callback){
      this._callback = callback;
      this.makePlansSelectable(planIds);
    },

    makePlansSelectable(planIds){
      dojo.query(".plan-card-holder").removeClass("selected");
      dojo.query(".plan-card-holder").addClass("unselectable");
      this._selectablePlans = planIds;
      planIds.forEach(planId =>  dojo.query("#plan-card-" + planId).removeClass("unselectable").addClass("selectable") );
    },

    onClickPlan(planId){
      debug("Clicked on a plan", planId);
      // Check if selectable
      if(!this._selectablePlans || !this._selectablePlans.includes(planId))
        return;

      this._callback(planId)
    },
  });
});
