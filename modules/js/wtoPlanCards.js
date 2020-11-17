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
      this._planIds = [];
      this.gamedatas = gamedatas;

      // Display the cards
      gamedatas.planCards.forEach(plan => {
        this._planIds.push(plan.id);
        dojo.place(this.format_block('jstpl_planCard', plan), 'plan-cards-container-resizable');
        dojo.connect($('plan-card-' + plan.id), 'click', () => this.onClickPlan(plan.id));
        // TODO : add tooltips
      });

      this.updateValidationMarks(gamedatas.planValidations);
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


    //////////////////////////////////////
    ////////  Display scored plan ////////
    //////////////////////////////////////
    updateValidationMarks(validations){
      validations.forEach((validations, i) => {
        var high = [], low = [];
        for(var pId in validations){
          var name = this.gamedatas.players[pId].name;
          if(validations[pId] == 0)
            high.push(name);
          else
            low.push(name);
        }

        if(high.length > 0){
          var textHigh = _("Highest score: ") + high.join(",");
          this.addTooltip("plan-card-" + this._planIds[i] + "-0", textHigh, "");
          dojo.addClass("plan-card-" + this._planIds[i], "approved");
        }

        if(low.length > 0){
          var textLow = _("Lower score: ") + low.join(",");
          this.addTooltip("plan-card-" + this._planIds[i] + "-1", textLow, "");
        }
      })
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
