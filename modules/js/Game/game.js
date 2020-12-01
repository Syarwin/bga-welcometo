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

      this._dockedlog_to_move_id = {};
      this._customlog_to_move_id = {};
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

      // Handle previously cancelled moves
      this.cancelLogs(this.gamedatas.cancelMoveIds);
    },




    /*
     * Setup:
     */
    setup(gamedatas) {
      this.setupNotifications();
      this.initPreferencesObserver();
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
     takeAction(action, data, callback) {
       data = data || {};
       data.lock = true;
       callback = callback || function(res){ };
       this.ajaxcall("/" + this.game_name + "/" + this.game_name + "/" + action + ".html", data, this, callback);
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
         debug('Ignoring startActionTimer(' + buttonId + ')', 'readOnly=' + isReadOnly, 'prefValue=' + prefValue);
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
      var currentLogId = this.next_log_id;
      this.inherited(arguments);

      if (msg.move_id && this.next_log_id != currentLogId) {
        var moveId = +msg.move_id;
        this._dockedlog_to_move_id[currentLogId] = moveId;
        this.checkLogCancel(moveId);
      }

      if(msg.args.moveId){
        debug("test");
        this._customlog_to_move_id[currentLogId] = msg.args.moveId;
        this.checkLogCancel(moveId);
      }
    },


    /*
     * cancelLogs:
     *   strikes all log messages related to the given array of move IDs
     */
    checkLogCancel(moveId){
      debug("Cancelled ids : ", this.gamedatas.cancelMoveIds)
      if (this.gamedatas.cancelMoveIds != null && this.gamedatas.cancelMoveIds.includes(moveId)) {
        this.cancelLogs([moveId]);
      }
    },

    cancelLogs (moveIds) {
      if (Array.isArray(moveIds)) {
        debug('Cancel log messages for move IDs', moveIds);
        debug(this._customlog_to_move_id);

        var elements = [];
        // Desktop logs
        for (var logId in this.log_to_move_id) {
          var moveId = +this.log_to_move_id[logId];
          if (moveIds.includes(moveId)) {
            elements.push($('log_' + logId));
          }
        }
        // Custom logs
        for (var logId in this._customlog_to_move_id) {
          var moveId = +this._customlog_to_move_id[logId];
          if (moveIds.includes(moveId)) {
            elements.push($('log_' + logId));
          }
        }


        // Mobile logs
        for (var logId in this._dockedlog_to_move_id) {
          var moveId = +this._dockedlog_to_move_id[logId];
          if (moveIds.includes(moveId)) {
            elements.push($('dockedlog_' + logId));
          }
        }

        debug(elements);
        // Add strikethrough
        elements.forEach(e => {
          if (e != null) {
            dojo.addClass(e, 'cancel');
          }
        });
      }
    },
  });
});
