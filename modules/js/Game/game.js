var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () {};

define(['dojo', 'dojo/_base/declare', 'ebg/core/gamegui'], (dojo, declare) => {
  return declare('customgame.game', ebg.core.gamegui, {
    /*
     * Constructor
     */
    constructor() {
      this._notifications = [['newPrivateState', 1]];

      this._notif_uid_to_log_id = {};
      this._last_notif = null;
    },

    /*
     * [Undocumented] Override BGA framework functions to call onLoadingComplete when loading is done
     */
    setLoader(value, max) {
      this.inherited(arguments);
      if (!this.isLoadingComplete && value >= 100) {
        this.isLoadingComplete = true;
        this.onLoadingComplete();
      }
    },

    onLoadingComplete() {
      debug('Loading complete');

      this.cancelLogs(this.gamedatas.canceledNotifIds);
    },

    /**
     * Overwrite showMessage to make sure error message get dumped into the console
     */
    showMessage(msg, type) {
      if (type == 'error') {
        console.error(msg);
      }
      return this.inherited(arguments);
    },

    /*
     * Setup:
     */
    setup(gamedatas) {
      this.setupNotifications();
      this.initPreferences();
      dojo.connect(this.notifqueue, 'addToLog', () => {
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
     * check = false because of private flow
     */
    takeAction(action, data, check = false, checkLock = true) {
      if (check && !this.checkAction(action)) return false;
      if (!check && checkLock && !this.checkLock()) return false;

      data = data || {};
      if (data.lock === undefined) {
        data.lock = true;
      } else if (data.lock === false) {
        delete data.lock;
      }
      return new Promise((resolve, reject) => {
        this.ajaxcall(
          '/' + this.game_name + '/' + this.game_name + '/' + action + '.html',
          data,
          this,
          (data) => resolve(data),
          (isError, message, code) => {
            if (isError) reject(message, code);
          },
        );
      });
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
      if (args.parallel) {
        if (args.args._private) this.setupPrivateState(args.args._private.state, args.args._private.args);
        return;
      }

      // Call appropriate method
      var methodName = 'onEnteringState' + stateName.charAt(0).toUpperCase() + stateName.slice(1);
      if (this[methodName] !== undefined) this[methodName](args.args);
    },

    /*
     * Private state
     */
    setupPrivateState(state, args) {
      if (this.gamedatas.gamestate.parallel) delete this.gamedatas.gamestate.parallel;
      this.gamedatas.gamestate.name = state.name;
      this.gamedatas.gamestate.descriptionmyturn = state.descriptionmyturn;
      this.gamedatas.gamestate.possibleactions = state.possibleactions;
      this.gamedatas.gamestate.transitions = state.transitions;
      this.gamedatas.gamestate.args = args;
      this.updatePageTitle();
      this.onEnteringState(state.name, this.gamedatas.gamestate);
    },

    ntf_newPrivateState() {},

    notif_newPrivateState(n) {
      this.onLeavingState(this.gamedatas.gamestate.name);
      this.setupPrivateState(n.args.state, n.args.args);
    },

    notif_synchro(n) {
      if (this.isReadOnly && n.args.player_id != this.player_id) {
        this.onArchiveNext({ preventDefault: () => {} });
      }
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
    clearPossible() {},

    /*
     * setupNotifications
     */
    setupNotifications() {
      console.log(this._notifications);
      this._notifications.forEach((notif) => {
        var functionName = 'notif_' + notif[0];

        dojo.subscribe(notif[0], this, functionName);
        if (notif[1] !== undefined) {
          if (notif[1] === null) {
            this.notifqueue.setSynchronous(notif[0]);
          } else {
            this.notifqueue.setSynchronous(notif[0], notif[1]);

            // xxxInstant notification runs same function without delay
            dojo.subscribe(notif[0] + 'Instant', this, functionName);
            this.notifqueue.setSynchronous(notif[0] + 'Instant', 10);
          }
        }

        if (notif[2] != undefined) {
          this.notifqueue.setIgnoreNotificationCheck(notif[0], notif[2]);
        }
      });

      dojo.subscribe('synchro', this, 'notif_synchro');
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
      dojo.connect($(buttonId), 'click', () => this.stopActionTimer());
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
    addPrimaryActionButton(id, text, callback) {
      if (!$(id)) this.addActionButton(id, text, callback, 'customActions', false, 'blue');
    },

    addSecondaryActionButton(id, text, callback) {
      if (!$(id)) this.addActionButton(id, text, callback, 'customActions', false, 'gray');
    },

    addDangerActionButton(id, text, callback) {
      if (!$(id)) this.addActionButton(id, text, callback, 'customActions', false, 'red');
    },

    /*
     * Preference polyfill
     */

    /*
     * Preference polyfill
     */
    setPreferenceValue(number, newValue) {
      var optionSel = 'option[value="' + newValue + '"]';
      dojo
        .query(
          '#preference_control_' + number + ' > ' + optionSel + ', #preference_fontrol_' + number + ' > ' + optionSel,
        )
        .attr('selected', true);
      var select = $('preference_control_' + number);
      if (dojo.isIE) {
        select.fireEvent('onchange');
      } else {
        var event = document.createEvent('HTMLEvents');
        event.initEvent('change', false, true);
        select.dispatchEvent(event);
      }
    },

    initPreferencesObserver() {
      dojo.query('.preference_control, preference_fontrol').on('change', (e) => {
        var match = e.target.id.match(/^preference_[fc]ontrol_(\d+)$/);
        if (!match) {
          return;
        }
        var pref = match[1];
        var newValue = e.target.value;
        this.prefs[pref].value = newValue;
        if (this.prefs[pref].attribute) {
          $('ebd-body').setAttribute('data-' + this.prefs[pref].attribute, newValue);
        }

        $('preference_control_' + pref).value = newValue;
        if ($('preference_fontrol_' + pref)) {
          $('preference_fontrol_' + pref).value = newValue;
        }
        data = { pref: pref, lock: false, value: newValue, player: this.player_id };
        this.takeAction('actChangePref', data, false, false);
        this.onPreferenceChange(pref, newValue);
      });
    },

    checkPreferencesConsistency(backPrefs) {
      backPrefs.forEach((prefInfo) => {
        let pref = prefInfo.pref_id;
        if (this.prefs[pref] != undefined && this.prefs[pref].value != prefInfo.pref_value) {
          data = { pref: pref, lock: false, value: this.prefs[pref].value, player: this.player_id };
          this.takeAction('actChangePref', data, false, false);
        }
      });
    },

    onPreferenceChange(pref, newValue) {},

    // Init preferences will setup local preference and put the corresponding data-attribute on overall-content div if needed
    initPreferences() {
      // Attach data attribute on overall-content div
      Object.keys(this.prefs).forEach((prefId) => {
        let pref = this.prefs[prefId];
        if (pref.attribute) {
          $('ebd-body').setAttribute('data-' + pref.attribute, pref.value);
        }
      });

      if (!this.isReadOnly() && this.gamedatas.localPrefs) {
        // Create local prefs
        Object.keys(this.gamedatas.localPrefs).forEach((prefId) => {
          let pref = this.gamedatas.localPrefs[prefId];
          pref.id = prefId;
          let selectedValue = this.gamedatas.prefs.find((pref2) => pref2.pref_id == pref.id).pref_value;
          pref.value = selectedValue;
          this.prefs[prefId] = pref;
          this.place('tplPreferenceSelect', pref, 'local-prefs-container');
        });
      }

      this.initPreferencesObserver();
      if (!this.isReadOnly()) {
        this.checkPreferencesConsistency(this.gamedatas.prefs);
      }
    },

    tplPreferenceSelect(pref) {
      let values = Object.keys(pref.values)
        .map(
          (val) =>
            `<option value='${val}' ${pref.value == val ? 'selected="selected"' : ''}>${_(
              pref.values[val].name,
            )}</option>`,
        )
        .join('');

      return `
              <div class="preference_choice">
                <div class="row-data row-data-large">
                  <div class="row-label">${_(pref.name)}</div>
                  <div class="row-value">
                    <select id="preference_control_${
                      pref.id
                    }" class="preference_control game_local_preference_control" style="display: block;">
                      ${values}
                    </select>
                  </div>
                </div>
              </div>
            `;
    },

    /*
     * [Undocumented] Called by BGA framework on any notification message
     * Handle cancelling log messages for restart turn
     */
    onPlaceLogOnChannel(msg) {
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
    checkLogCancel(notifId) {
      if (this.gamedatas.canceledNotifIds != null && this.gamedatas.canceledNotifIds.includes(notifId)) {
        this.cancelLogs([notifId]);
      }
    },

    cancelLogs(notifIds) {
      notifIds.forEach((uid) => {
        if (this._notif_uid_to_log_id.hasOwnProperty(uid)) {
          let logId = this._notif_uid_to_log_id[uid];
          if ($('log_' + logId)) dojo.addClass('log_' + logId, 'cancel');
        }
      });
    },
  });
});
