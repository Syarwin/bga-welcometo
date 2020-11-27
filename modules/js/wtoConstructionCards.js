var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define(["dojo", "dojo/_base/declare","ebg/core/gamegui",], function (dojo, declare) {
  const marks = [0, 1, 5, 0, 0, 2, 0, 3, 6, 0, 7, 1, 0, 4, 0, 2, 0, 0, 3, 0, 5, 0, 0, 1, 4, 7, 0, 6, 2, 0, 3, 5, 0, 2];

  return declare("bgagame.wtoConstructionCards", ebg.core.gamegui, {
/****************************************
******* Constructions cards class *******
*****************************************
 * create the layout for the cards
 * handle the clicks event to ask user to select card
 * animate cards at the beggining of a new turn
 */

    constructor(gamedatas) {
      debug("Seting up the cards", gamedatas.options.standard? "Standard mode" : "Only one card by stack");
      this._isStandard = gamedatas.options.standard; // Standard = playing with three stack

      // Adjust stack size for flip animation
      if(this._isStandard)
        dojo.addClass("construction-cards-container-resizable", "standard");

      let markedStack = Math.floor(Math.random() * 3);
      gamedatas.constructionCards.forEach((stack, i) => {
        stack.forEach((card,j) => {
          card.mark = i == markedStack && j == 0? marks[gamedatas.turn] : 0;
          dojo.place(this.format_block('jstpl_constructionCard', card), 'construction-cards-stack-' + i);
          dojo.style("construction-card-" + card.id, "z-index", 100);
          if(j == 0 && this._isStandard){ // Flip first card
            this.flipCard("construction-card-" + card.id, 1);
          }
        });

        dojo.connect($('construction-cards-stack-' + i), 'click', () => this.onClickStack(i));
      });
    },


    // Clear everything
    clearPossible(){
      this._callback = null;
      this._possibleChoices = null;
      this._selectableStacks = null;
      this._highlighted = null;
      dojo.query(".construction-cards-stack").removeClass("unselectable selectable");
    },

    // Hightlight selected stack(s)
    highlight(stack, callback){
      this._callback = callback;
      this._highlighted = stack;
      dojo.query(".construction-cards-stack").addClass("unselectable");
      if(this._isStandard){
        dojo.addClass('construction-cards-stack-' + stack, 'selected');
      } else {
        dojo.addClass('construction-cards-stack-' + stack[0], 'selected');
        dojo.addClass('construction-cards-stack-' + stack[1], 'selected flipped');
      }
    },

    // Flip card and add tooltip
    flipCard(card, turn){
      dojo.addClass(card, 'flipped');
      setTimeout(() => {
        dojo.style(card, "z-index", turn);

        let action = dojo.attr(card, 'data-action');
        let tooltipContent = [
          '',
          _("Build a fence between two houses on the same streets to create housing estates"),
          _("Promotes and increase the value of completed housing estates"),
          _("Build a park in the same street that the house number is written"),
          _("If the number is written in a house with a planned pool, you may build that pool"),
          _("Allow you to add or substract 1 or 2 to the house number, and cross one box from the Temp Agency column (majority scoring)"),
          _("Allow you to write a second house number by duplicating an already existing number next to it. Cross one space in the 'bis' column (negative scoring)")
        ];
        this.addTooltip(card, tooltipContent[action], "");
      }, 1000);
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


    makeStacksSelectable(stacks, flipped){
      dojo.query(".construction-cards-stack").removeClass("selected flipped"); // TODO : add in the clearPossible function instead ?
      dojo.query(".construction-cards-stack").addClass("unselectable");
      this._selectableStacks = stacks;
      stacks.forEach(stackId =>  dojo.query("#construction-cards-stack-" + stackId).removeClass("unselectable").addClass("selectable" + (flipped? " flipped" :"")) );
    },

    onClickStack(stackId){
      debug("Clicked on a stack", stackId);
      // Check if selectable
      if((!this._selectableStacks || !this._selectableStacks.includes(stackId)) && this._selectedStackForNonStandard != stackId){
        // Clicked on a selected card => callback to restart turn
        if(this._highlighted != null && this._callback != null && (
            (this._isStandard && this._highlighted == stackId)
          ||(!this._isStandard && this._highlighted.includes(stackId))
        ))
          this._callback();
        return;
      }

      // Standard mode => return stack id
      if(this._isStandard)
        this._callback(stackId)
      else
        this.onClickStackNonStandard(stackId);
    },


    //////////////////////////////////////
    /////////////  New turn  /////////////
    //////////////////////////////////////
    newTurn(cards, turn){
      // Clear everything
      dojo.query(".construction-cards-stack").removeClass("selected selectable unselectable");

      // Marks ?
      let markedStack = Math.floor(Math.random() * 3);
      debug(markedStack,marks[turn]);
      cards.forEach(card => {
        // Add small mark
        card.mark = card.stackId == markedStack? marks[turn] : 0;

        let cardsInStack = dojo.query("#construction-cards-stack-" + card.stackId + " .construction-card-holder:last-of-type");
        // NULL only happens in EXPERT MODE
        let oldCard = cardsInStack.length == 0? null : cardsInStack[0];

        //// STANDARD MODE : FLIP CARD ////
        if(this._isStandard){
          // Flip card animation
          if(oldCard)
            this.flipCard(oldCard, turn);

          // New card
          if($("construction-card-" + card.id))
            dojo.destroy("construction-card-" + card.id);
          var newCard = dojo.place(this.format_block('jstpl_constructionCard', card), 'construction-cards-stack-' + card.stackId);
          dojo.style("construction-card-" + card.id, "z-index", 100 - turn);

          // First card in this stack ? => slide from left
          if(!oldCard)
            this.slideFromLeft(newCard);
        }
        //// NON STANDARD MODE : SLIDE LEFT ////
        else {
          // Compute x position to make it slide out the left border of window
          let stack = $("construction-cards-stack-" + card.stackId);
          this.slideToLeftAndDestroy(oldCard)

          setTimeout(() => {
            // Remove flipped class if needed
            dojo.addClass(stack, 'notransition')
            dojo.removeClass(stack, "flipped");
            stack.offsetHeight;
            dojo.removeClass(stack, 'notransition');

            // Create a new card and put it to the left (hidden)
            var newCard = dojo.place(this.format_block('jstpl_constructionCard', card), stack);
            dojo.style(newCard, "z-index", 100 - turn);
            this.slideFromLeft(newCard);
          }, 800);
        }
      });
    },

    slideFromLeft(elem){
      let stack = elem.parentNode;
      let x = (elem.offsetWidth + stack.offsetWidth + stack.offsetLeft + 30);
      dojo.addClass(elem, 'notransition')
      dojo.style(elem, "opacity", "0");
      dojo.style(elem, "left", -x + "px");
      elem.offsetHeight;
      dojo.removeClass(elem, 'notransition');

      dojo.style(elem, "opacity", "1");
      dojo.style(elem, "left", "0px")
    },


    slideToLeftAndDestroy(elem){
      if(elem == null)
        return;

      let stack = elem.parentNode;
      let x = (elem.offsetWidth + stack.offsetWidth + stack.offsetLeft + 30);

      dojo.style(elem, "left", -x + "px");
      setTimeout(() => {
        dojo.destroy(elem);
      }, 800);
    },

    discard(){
      dojo.query(".construction-card-holder").forEach(elem => this.slideToLeftAndDestroy(elem));
    },

    giveCard(stack, pId){
      let oldCard = dojo.query("#construction-cards-stack-" + stack + " .construction-card-holder:last-of-type")[0];
      dojo.addClass(oldCard, 'notransition')
      this.slideToObjectAndDestroy(oldCard, "overall_player_board_" + pId, 1000);
    },


    ////////////////////////////////////////////////////////
    ////////  Non-standard mode : select two stacks ////////
    ////////////////////////////////////////////////////////

    /*
     * Expert/solo mode => need two stacks
     */
    onClickStackNonStandard(stackId){
      // Click again on same card => unselect
      if(this._selectedStackForNonStandard == stackId){
        this.unselectFirstStack();
        return;
      }

      // First stack => ask for a second one
      if(this._selectedStackForNonStandard == null){
        this._selectedStackForNonStandard = stackId;
        // Compute new possible choices for stacks
        this.makeStacksSelectable(this.getSelectableSecondStacks(stackId), true);
        this.addActionButton('buttonUnselect', _('Unselect'), () => this.unselectFirstStack(), null, false, 'gray');
        dojo.addClass("construction-cards-stack-" + stackId, "selected");
      }

      // Second stack => return both stacks
      else {
        this._callback([this._selectedStackForNonStandard, stackId]);
      }
    },

    // Get the available choices for second stack depending on the first stack selected
    getSelectableSecondStacks(stackId){
      return this._possibleChoices.reduce( (stacks, choice) => {
        if(choice[0] == stackId)
          stacks.push(choice[1]);
        return stacks;
      }, []);
    },

    // Unselect first stack
    unselectFirstStack(){
      dojo.destroy("buttonUnselect");
      dojo.removeClass("construction-cards-stack-" + this._selectedStackForNonStandard, "selected");
      this.initSelectableStacks();
    },
  });
});
