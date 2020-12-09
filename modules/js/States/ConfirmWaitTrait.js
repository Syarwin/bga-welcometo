define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  const CONFIRM = 102;
  const CONFIRM_TIMER = 1;
  const CONFIRM_ENABLED = 2;
  const CONFIRM_DISABLED = 3;


  return declare("welcometo.confirmWaitTrait", null, {
    constructor(){
      this._notifications.push(
        ['clearTurn', 1],
      );
    },

    ///////////////////////////////////////
    ///////////////////////////////////////
    /////////   Confirm/undo turn   ///////
    ///////////////////////////////////////
    ///////////////////////////////////////
    onEnteringStateConfirmTurn(args){
      this.displayBasicInfo(args);
      this.addPrimaryActionButton("buttonConfirmAction", _("Confirm"), 'onClickConfirmTurn');

      // Launch timer on button depending on pref
      var pref = 1;
      if(this.prefs[CONFIRM].value == CONFIRM_DISABLED) pref = 0;
      if(this.prefs[CONFIRM].value == CONFIRM_ENABLED) pref = 2;
      debug(this.prefs, pref)
      this.startActionTimer('buttonConfirmAction', 10, pref);
    },

    onClickConfirmTurn(){
      this.takeAction("confirmTurn");
    },

    onClickCancelTurn(){
      this.takeAction("cancelTurn");
    },

    notif_clearTurn(n){
      debug("Notif: restarting turn", n);
      this._scoreSheet.clearTurn(n.args.turn);
      this._planCards.clearTurn(n.args.turn);
      this.cancelLogs(n.args.notifIds);
    },


    onEnteringStateWaitOthers(args){
      this.displayBasicInfo(args);
    },

  });
});
