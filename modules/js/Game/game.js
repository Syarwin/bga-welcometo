var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };


define(["dojo", "dojo/_base/declare","ebg/core/gamegui",], (dojo, declare) => {
  return declare("customgame.game", ebg.core.gamegui, {
    /*
     * Constructor
     */
    constructor() {
      this._notifications = [
        ['newPrivateState', 1],
      ];

      this._notif_uid_to_log_id = {};
      this._last_notif = null;
    },



    /*
     * [Undocumented] Override BGA framework functions to call onLoadingComplete when loading is done
     */
    setLoader(value,max){
      this.inherited(arguments);
      if (!this.isLoadingComplete && value >= 100) {
        this.isLoadingComplete = true;
        this.onLoadingComplete();
      }
    },

    onLoadingComplete(){
      debug('Loading complete');

      this.cancelLogs(this.gamedatas.canceledNotifIds);
    },




    /*
     * Setup:
     */
    setup(gamedatas) {
      this.setupNotifications();
      this.initPreferencesObserver();
      dojo.connect(this.notifqueue, "addToLog", () => {
        this.checkLogCancel(this._last_notif);
      });
     },


     /*
 		  * Detect if spectator or replay
 		  */
     isReadOnly() {
       return this.isSpectator || typeof g_replayFrom != 'undefined' || g_archive_mode;
     },


     /*
 		 * Make an AJAX call with automatic lock
 		 */
     takeAction(action, data, reEnterStateOnError) {
       data = data || {};
       data.lock = true;
       let promise = new Promise((resolve, reject) => {
         this.ajaxcall(
           "/" + this.game_name + "/" + this.game_name + "/" + action + ".html",
          data,
          this,
          (data) => resolve(data),
          (isError,message,code) => {
            if(isError)
              reject(message, code);
          });
       });

       if(reEnterStateOnError){
         promise.catch(() => this.onEnteringState(this.gamedatas.gamestate.name, this.gamedatas.gamestate) );
       }

       return promise;
     },


     /*
      * onEnteringState:
      * 	this method is called each time we are entering into a new game state.
      *
      * params:
      *  - str stateName : name of the state we are entering
      *  - mixed args : additional information
      */
     onEnteringState(stateName, args) {
       debug('Entering state: ' + stateName, args);

       // Private state machine
       if(args.parallel){
         if(args.args._private)
          this.setupPrivateState(args.args._private.state, args.args._private.args);
         return;
       }

       // Call appropriate method
       var methodName = "onEnteringState" + stateName.charAt(0).toUpperCase() + stateName.slice(1);
       if (this[methodName] !== undefined)
         this[methodName](args.args);
     },


     /*
      * Private state
      */
     setupPrivateState(state, args){
       if(this.gamedatas.gamestate.parallel)
         delete this.gamedatas.gamestate.parallel;
       this.gamedatas.gamestate.name = state.name;
       this.gamedatas.gamestate.descriptionmyturn = state.descriptionmyturn;
       this.gamedatas.gamestate.possibleactions = state.possibleactions;
       this.gamedatas.gamestate.transitions = state.transitions;
       this.gamedatas.gamestate.args = args;
       this.updatePageTitle();
       this.onEnteringState(state.name, this.gamedatas.gamestate);
     },

     notif_newPrivateState(n){
       this.onLeavingState(this.gamedatas.gamestate.name);
       this.setupPrivateState(n.args.state, n.args.args);
     },

     /*
      * onLeavingState:
      * 	this method is called each time we are leaving a game state.
      *
      * params:
      *  - str stateName : name of the state we are leaving
      */
     onLeavingState(stateName) {
       debug('Leaving state: ' + stateName);
       this.clearPossible();
     },
     clearPossible(){

     },


     /*
      * setupNotifications
      */
     setupNotifications() {
       this._notifications.forEach(notif => {
         var functionName = "notif_" + notif[0];

         dojo.subscribe(notif[0], this, functionName);
         if(notif[1] != null){
           this.notifqueue.setSynchronous(notif[0], notif[1]);

           // xxxInstant notification runs same function without delay
           dojo.subscribe(notif[0] + 'Instant', this, functionName);
           this.notifqueue.setSynchronous(notif[0] + 'Instant', 10);
         }
       });
     },



     /*
      * Add a timer on an action button :
      * params:
      *  - buttonId : id of the action button
      *  - time : time before auto click
      *  - pref : 0 is disabled (auto-click), 1 if normal timer, 2 if no timer and show normal button
      */

     startActionTimer(buttonId, time, pref) {
       var button = $(buttonId);
       var isReadOnly = this.isReadOnly();
       if (button == null || isReadOnly || pref == 2) {
         debug('Ignoring startActionTimer(' + buttonId + ')', 'readOnly=' + isReadOnly, 'prefValue=' + pref);
         return;
       }

       // If confirm disabled, click on button
       if (pref == 0) {
         button.click();
         return;
       }

       this._actionTimerLabel = button.innerHTML;
       this._actionTimerSeconds = time;
       this._actionTimerFunction = () => {
         var button = $(buttonId);
         if (button == null) {
           this.stopActionTimer();
         } else if (this._actionTimerSeconds-- > 1) {
           button.innerHTML = this._actionTimerLabel + ' (' + this._actionTimerSeconds + ')';
         } else {
           debug('Timer ' + buttonId + ' execute');
           button.click();
         }
       };
       dojo.connect($(buttonId), "click", () => this.stopActionTimer());
       this._actionTimerFunction();
       this._actionTimerId = window.setInterval(this._actionTimerFunction, 1000);
       debug('Timer #' + this._actionTimerId + ' ' + buttonId + ' start');
     },

     stopActionTimer() {
       if (this._actionTimerId != null) {
         debug('Timer #' + this._actionTimerId + ' stop');
         window.clearInterval(this._actionTimerId);
         delete this._actionTimerId;
       }
     },







     /*
      * Play a given sound that should be first added in the tpl file
      */
     playSound(sound, playNextMoveSound = true) {
       playSound(sound);
       playNextMoveSound && this.disableNextMoveSound();
     },


     /*
      * Add a blue/grey button if it doesn't already exists
      */
     addPrimaryActionButton(id, text, callback){
       if(!$(id))
        this.addActionButton(id, text, callback, "customActions", false, 'blue');
     },

     addSecondaryActionButton(id, text, callback){
       if(!$(id))
        this.addActionButton(id, text, callback, "customActions", false, 'gray');
     },

     addDangerActionButton(id, text, callback){
       if(!$(id))
        this.addActionButton(id, text, callback, "customActions", false, 'red');
     },


     /*
      * Preference polyfill
      */
     setPreferenceValue(number, newValue) {
 			var optionSel = 'option[value="' + newValue + '"]'
 			dojo.query('#preference_control_' + number + ' > ' +	optionSel
         +	', #preference_fontrol_' +number + ' > ' +	optionSel)
 				.attr('selected', true)
 			var select = $('preference_control_' + number)
 			if (dojo.isIE) {
 				select.fireEvent('onchange')
 			} else {
 				var event = document.createEvent('HTMLEvents')
 				event.initEvent('change', false, true)
 				select.dispatchEvent(event)
 			}
 		},

 		initPreferencesObserver() {
 			dojo.query('.preference_control').on(
 				'change', (e) => {
 					var match = e.target.id.match(/^preference_control_(\d+)$/)
 					if (!match) {
 						return
 					}
 					var pref = match[1]
 					var newValue = e.target.value
 					this.prefs[pref].value = newValue
 					this.onPreferenceChange(pref, newValue)
 			})
 		},

    onPreferenceChange(pref, newValue){
    },






    /*
     * [Undocumented] Called by BGA framework on any notification message
     * Handle cancelling log messages for restart turn
     */
    onPlaceLogOnChannel(msg){
      var currentLogId = this.notifqueue.next_log_id;
      var res = this.inherited(arguments);
      this._notif_uid_to_log_id[msg.uid] = currentLogId;
      this._last_notif = msg.uid;
      return res;
    },



    /*
     * cancelLogs:
     *   strikes all log messages related to the given array of notif ids
     */
    checkLogCancel(notifId){
      if (this.gamedatas.canceledNotifIds != null && this.gamedatas.canceledNotifIds.includes(notifId)) {
        this.cancelLogs([notifId]);
      }
    },

    cancelLogs(notifIds) {
      notifIds.forEach(uid => {
        if(this._notif_uid_to_log_id.hasOwnProperty(uid)){
          let logId = this._notif_uid_to_log_id[uid];
          if($('log_' + logId))
            dojo.addClass('log_' + logId, "cancel");
        }
      });
    },
  });
});
