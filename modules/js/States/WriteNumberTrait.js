define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  return declare("welcometo.writeNumberTrait", null, {
    constructor(){
      this._notifications.push(
        ['writeNumber', 1000],
      );
    },


    ////////////////////////////////////////////
    //////   Choose construction cards   ///////
    ////////////////////////////////////////////
    onEnteringStateChooseCards(args){
      this.displayBasicInfo(args);
      this._constructionCards.promptPlayer(args.selectableStacks, this.onChooseCards.bind(this));

      if(args.selectableStacks.length == 0){
        // Permit refusal
        this.gamedatas.gamestate.descriptionmyturn = _("You cannot write a number and must take a permit refusal");
        this.updatePageTitle();

        let callback = (zone) => this.takeAction("permitRefusal", {}, true);
        this._scoreSheet.promptZones("permit-refusal", args.zones, callback);
        this.addDangerActionButton("btnPermitRefusal", _("Permit refusal"), callback);
      }

      if(args.canBuildRoundabout){
        this.addPrimaryActionButton("btnRoundabout", _("Build a roundabout"), () => this.takeAction("roundabout"));
      }
    },

    onChooseCards(choice){
      debug("You chose construction cards :", choice);
      if(this._isStandard){
        this.takeAction("chooseStack", { stack: choice});
      } else {
        this.takeAction("chooseStacks", { numberStack: choice[0], actionStack: choice[1] });
      }
    },



    ////////////////////////////////////////////
    ///////   Draw a number on a house   ///////
    ////////////////////////////////////////////
    onEnteringStateWriteNumber(args){
      this.displayBasicInfo(args);
      this._scoreSheet.promptNumbers(args.numbers, this.onChooseNumber.bind(this));

      if(args.refusal){
        let callback = (zone) => this.takeAction("permitRefusal");
        this._scoreSheet.promptZones("permit-refusal", args.refusal, callback);
        this.addDangerActionButton("btnPermitRefusal", _("Permit refusal"), callback);
      }
    },

    onChooseNumber(number, x, y){
      debug("You chose to write", number, " at location ", x, y);
      this.takeAction("writeNumber", { number: number, x:x, y:y}, true);
      this.clearPossible();
    },

    notif_writeNumber(n){
      debug("Notif: writing a number on a house", n);
      this._scoreSheet.addHouseNumber(n.args.house, true);
    },


    ///////////////////////////////////
    /////////   Roundabout   //////////
    ///////////////////////////////////
    onEnteringStateBuildRoundabout(args){
      this.onEnteringStateWriteNumber(args);
      this.addPassActionButton();
    },
  });
});
