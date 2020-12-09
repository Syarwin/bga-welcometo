define(["dojo", "dojo/_base/declare"], (dojo, declare) => {
  const AUTOMATIC = 101;
  const DISABLED = 1;
  const ENABLED = 2;

  return declare("welcometo.actionsTrait", null, {
    constructor(){
      this._notifications.push(
        ['addScribble', 1000],
        ['addMultipleScribbles', 1000]
      );
    },

    /*
     * Add a scribble to a zone
     */
    notif_addScribble(n){
      debug("Notif: scribbling a zone", n);
      this._scoreSheet.addScribble(n.args.scribble, true);
    },


    notif_addMultipleScribbles(n){
      debug("Notif: scribbling several zones", n);
      n.args.scribbles.forEach(scribble => this._scoreSheet.addScribble(scribble, true) );
    },


    ////////////////////////////////////////////
    ////////////////////////////////////////////
    ////////   Non-automatic actions   /////////
    ////////////////////////////////////////////
    ////////////////////////////////////////////
    addPassActionButton(){
      this.addPrimaryActionButton("buttonPassAction", _("Pass"), 'onClickPassAction');
    },

    onClickPassAction(){
      this.takeAction("passAction", {}, true);
    },


   //////////////////////////////////////
   ////////   Generic zones   ///////////
   //////////////////////////////////////
   /*
    * Generic handling of most zones : estate score, pool, parks, ...
    */
   promptZones(type, args, automatic){
     this.displayBasicInfo(args);

     // Automatic some action with user preference
     if(automatic && args.zones.length == 1 && this.prefs[AUTOMATIC].value == ENABLED){
       this.singleZoneSelect(args.zones)
       return;
     }

     this.addPassActionButton();
     this._scoreSheet.promptZones(type, args.zones,  (zone) => {
       this.takeAction('scribbleZone', zone, true);
     });
   },

   singleZoneSelect(zones){
     var zone = { x : zones[0][0] };
     if(zones[0].length == 2)
       zone.y = zones[0][1];

     this._scoreSheet.clearPossible();
     this.takeAction('scribbleZone', zone);
   },


    // Estate
    onEnteringStateActionSurveyor(args){
      this.promptZones("estate-fence", args);
    },

    // Estate
    onEnteringStateActionEstate(args){
      this.promptZones("score-estate", args);
    },

    // Parks
    onEnteringStateActionPark(args){
      this.promptZones("park", args, true);
      this.addPrimaryActionButton("btnBuildPark", _("Build park"), () => this.singleZoneSelect(args.zones));
    },

    // Pools
    onEnteringStateActionPool(args){
      this._scoreSheet.promptPool(args.lastHouse);
      this.promptZones("score-pool", args, true);
      this.addPrimaryActionButton("btnBuildPool", _("Build pool"), () => this.singleZoneSelect(args.zones));
    },



    //////////////////////////////////////
    //////////   Bis action   ////////////
    //////////////////////////////////////
    onEnteringStateActionBis(args){
      this.displayBasicInfo(args);
      this.addPassActionButton();
      this._scoreSheet.promptNumbers(args.numbers, this.onChooseNumberBis.bind(this));
    },

    onChooseNumberBis(number, x, y){
      debug("You chose to write", number, " bis at location ", x, y);
      this.takeAction("writeNumberBis", { number: number, x:x, y:y}, true);
    },

  });
});
