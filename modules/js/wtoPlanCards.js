var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define(["dojo", "dojo/_base/declare","ebg/core/gamegui",], function (dojo, declare) {
  return declare("welcometo.planCards", ebg.core.gamegui, {
/****************************************
********* City Plan cards class *********
*****************************************
 * create the layout for the cards
 */

    constructor(gamedatas, pId) {
      debug("Seting up the plan cards");
      this._selectablePlans = [];
      this._planIds = [];
      this._validations = gamedatas.planValidations;
      this._gamedatas = gamedatas;
      this._pId = pId;

      // Display the cards
      gamedatas.planCards.forEach(plan => {
        this._planIds.push(plan.id);
        var div = dojo.place(this.format_block('jstpl_planCard', plan), 'plan-cards-container-resizable');
        dojo.connect($('plan-card-' + plan.id), 'click', () => this.onClickPlan(plan.id));

        let desc = plan.desc.map(t => _(t)).join("\n");
        this.addTooltip(div.id, desc, '');
      });

      this.updateValidationMarks();
    },

    // Clear everything
    clearPossible(){
      this._callback = null;
      this._selectablePlans = null;
      dojo.query(".plan-card-holder").removeClass("unselectable selectable selected");
    },

    // Clear the turn
    clearTurn(turn){
      for(var i = 0; i < 3; i++){
        var temp = [];
        for(var pId in this._validations[i]){
          if(this._validations[i][pId].turn != turn)
            temp[pId] = this._validations[i][pId];
        }

        this._validations[i] = temp;
      }

      this.updateValidationMarks();
    },


    // Hightlight selected plan
    highlight(plans){
      dojo.query(".plan-card-holder").addClass("unselectable");
      plans.forEach(planId => dojo.addClass('plan-card-' + planId, 'selected') );
    },


    //////////////////////////////////////
    ////////  Display scored plan ////////
    //////////////////////////////////////
    updateValidations(validations){
      this._validations = validations;
      this.updateValidationMarks();
    },

    updateValidationMarks(){
      this._validations.forEach((validations, i) => {
        var id = "plan-card-" + this._planIds[i];

        var high = [], low = [];
        for(var pId in validations){
          var name = pId == -1? _("Solo") : this._gamedatas.players[pId].name;
          if(validations[pId].rank == 0)
            high.push(name);
          else
            low.push(name);
        }

        // If current player achieved this plan, display it
        var val = null;
        if(validations[this._pId] !== undefined){
          this.validateCurrentPlayerPlan(this._planIds[i], validations[this._pId]);
          val = validations[this._pId].rank;
        }

        // Add tooltip on highest score
        if(high.length > 0){
          var textHigh = _("Highest score: ") + high.join(",");
          this.addTooltip("plan-card-" + this._planIds[i] + "-0", textHigh, "");
          val = (val == null)? 1 : val;
        }

        // Add tooltip on lower score
        if(low.length > 0){
          var textLow = _("Lower score: ") + low.join(",");
          this.addTooltip("plan-card-" + this._planIds[i] + "-1", textLow, "");
        }

        dojo.attr(id, "data-validation", val);
      })
    },


    validateCurrentPlayerPlan(planId, validation, animation){
      if($("scribble-plan-" + planId))
        return;

      dojo.attr("plan-card-" + planId, "data-validation", validation.rank);

      var scribble = {
        turn: validation.turn,
        id: "plan-" + planId,
      };
      dojo.place(this.format_block("jstpl_scribbleCircle", scribble), "plan-card-" + planId + "-" + validation.rank);

      if(animation){
        playSound("welcometo_scribble");
        $("scribble-" + scribble.id).classList.add("animate");
      }
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
