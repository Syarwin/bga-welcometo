define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("welcometo.planValidationTrait", null, {
    constructor(){
      this._notifications.push(
        ['reshuffle', 1000],
        ['scorePlan', 1000],
      );
    },


    ///////////////////////////////////////////////////
    //////   Choose city plans for validation   ///////
    //////////////////////////////////////////////////
    onEnteringStateChoosePlan(args){
      this.displayBasicInfo(args);
      this._planCards.promptPlayer(args.selectablePlans, this.onChoosePlan.bind(this));
      this.addDangerActionButton("buttonPassAction", _("Pass"), 'onClickPassAction');
    },

    onChoosePlan(planId){
      debug("You chose plan cards :", planId);
      this.takeAction("choosePlan", { plan: planId});
    },


    notif_scorePlan(n){
      debug("Notif: scoring plan", n);
      this._planCards.validateCurrentPlayerPlan(n.args.planId, n.args.validation, true);
    },



    onEnteringStateAskReshuffle(args){
      this.addPrimaryActionButton("btnReshuffle", _("Reshuffle"), () => this.takeAction('reshuffle') );
      this.addPassActionButton();
    },

    notif_reshuffle(n){
      debug("Notif: reshuffling cards", n);
      this._constructionCards.discard();
    },


    ///////////////////////////////////////////////////
    //////   Choose estates to validate plan   ///////
    //////////////////////////////////////////////////
    onEnteringStateValidatePlan(args){
      this.displayBasicInfo(args);
      this._scoreSheet.promptPlayerEstates(args.currentPlan, this.onChooseEstates.bind(this));
    },

    onChooseEstates(estates){
      debug("You chose following estates for the plan :", estates);
      this.takeAction("validatePlan", { planArg : JSON.stringify(estates) });
    },
  });
});
