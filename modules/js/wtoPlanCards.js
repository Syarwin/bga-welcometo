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

      // Display the cards
      gamedatas.planCards.forEach(plan => {
        dojo.place(this.format_block('jstpl_planCard', plan), 'plan-cards-container-resizable');
      });
    },

    // Clear everything
    clearPossible(){
    },

    ////////////////////////////////////
    ////////  Selecting a stack ////////
    ////////////////////////////////////
    promptPlayer(possibleChoices, callback){
      this._callback = callback;
      this._possibleChoices = possibleChoices;
      this.initSelectableStacks();
    },

    initSelectableStacks(){
      this._selectedStackForNonStandard = null;
      let stacks = this._possibleChoices.map(choice => this._isStandard? choice : choice[0]);
      this.makeStacksSelectable(stacks);
    },


    makeStacksSelectable(stacks){
      dojo.query(".construction-cards-stack").removeClass("selected"); // TODO : add in the clearPossible function instead ?
      dojo.query(".construction-cards-stack").addClass("unselectable");
      this._selectableStacks = stacks;
      stacks.forEach(stackId =>  dojo.query("#construction-cards-stack-" + stackId).removeClass("unselectable").addClass("selectable") );
    },

    onClickStack(stackId){
      debug("Clicked on a stack", stackId);
      // Check if selectable
      if(!this._selectableStacks.includes(stackId))
        return;

      // Standard mode => return stack id
      if(this._isStandard)
        this._callback(stackId)
      else
        this.onClickStackNonStandard(stackId);
    },

  });
});
