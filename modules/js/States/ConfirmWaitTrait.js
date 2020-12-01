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
      var prefs = {
        CONFIRM_ENABLED:0,
        CONFIRM_TIMER:1,
        CONFIRM_DISABLED:2,
      };
      this.startActionTimer('buttonConfirmAction', 10, prefs[this.prefs[CONFIRM]]);
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
      this.cancelLogs(n.args.moveIds);
    },


    onEnteringStateWaitOthers(args){
      this.displayBasicInfo(args);
    },

  });
});
