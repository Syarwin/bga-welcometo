var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define(["dojo", "dojo/_base/declare","ebg/core/gamegui",
  g_gamethemeurl + "modules/js/nouislider.min.js",
  g_gamethemeurl + "modules/js/sticky.min.js"
], function (dojo, declare, gameui, noUiSlider, Stickyfill) {
  let HORIZONTAL = 0;
  let VERTICAL = 1;

  return declare("bgagame.wtoLayout", ebg.core.gamegui, {
/*********************************
********* Layout Manager *********
*********************************/
    constructor() {
      debug("Seting up the layout manager");

/*
      debug(Stickyfill);
      Stickyfill.forceSticky();
      Stickyfill.add($("construction-cards-container-sticky"));
/*
      let param = {
        stickyBitStickyOffset: 60,
        useFixed: true,
        useGetBoundingClientRect: true,
      };
      stickybits("#construction-cards-container-sticky", param);
      stickybits("#plans-cards-container-sticky",  param);
*/

      dojo.place(jstpl_layoutControls, 'upperrightmenu', 'first');
      dojo.connect($('layout-button'), 'onclick', () => this.toggleControls() );
      dojo.connect($('layout-control-0'), 'onclick', () => this.setMode(HORIZONTAL, true) );
      dojo.connect($('layout-control-1'), 'onclick', () => this.setMode(VERTICAL, true) );

      this._firstHandle = this.getConfig('firstHandle', 20);
      this._secondHandle = this.getConfig('secondHandle', 90);
      this._merged = this.getConfig('merged', true);

      if(localStorage.getItem("wtoLayout") == null){
        dojo.addClass("layout-button", "undefined");
        this.setMode(HORIZONTAL);
      } else {
        this.setMode(localStorage.getItem("wtoLayout"));
      }

      var range = document.getElementById('layout-control-range');
      noUiSlider.create(range, {
        start: [this._firstHandle, this._secondHandle],
        step:1,
        margin:40,
        padding:5,
        range: {
          'min': [0],
          'max': [100]
        },
      });
      range.noUiSlider.on('slide', (arg) => this.setHandles(parseInt(arg[0]), parseInt(arg[1])) );
    },

    getConfig(value, v){
      return localStorage.getItem(value) == null? v : localStorage.getItem(value);
    },

    setMode(mode, writeInStorage){
      this._mode = mode;
      if(writeInStorage)
        localStorage.setItem('wtoLayout', mode);

      dojo.attr("overall-content", "data-mode", mode);
      dojo.attr("layout-controls", "data-mode", mode);
      dojo.attr("welcometo-container", "data-mode", mode);
      this.onScreenWidthChange();
    },

    toggleControls(){
      dojo.toggleClass('layout-controls-container', 'layoutControlsHidden')
    },

    setHandles(a,b){
      this._firstHandle = a;
      this._secondHandle = b;
      localStorage.setItem("wtoLayout", HORIZONTAL);
      localStorage.setItem("firstHandle", a);
      localStorage.setItem("secondHangle", b);
      this.onScreenWidthChange();
    },

    onScreenWidthChange(){
      /*
      dojo.style('page-content', 'zoom', '');
      dojo.style('page-title', 'zoom', '');
      dojo.style('right-side-first-part', 'zoom', '');
      */

      if(this._mode == HORIZONTAL)
        this.resizeHorizontal();
      else
        this.resizeVertical();
    },


    resizeHorizontal(){
      let box = $('welcometo-container').getBoundingClientRect();

      let sheetWidth = 1544;
      let sheetRatio = (this._secondHandle - this._firstHandle) / 100;
      let newSheetWidth = sheetRatio*box['width'];
      let sheetScale = sheetRatio*box['width'] / sheetWidth;
      dojo.style("player-score-sheet-resizable", "transform", `scale(${sheetScale})`);
      dojo.style("player-score-sheet", "width", `${newSheetWidth}px`);
      dojo.style("player-score-sheet", "height", `${newSheetWidth}px`);

      let cardsWidth = 433;
      let cardsHeight = 963;
      let cardsRatio = this._firstHandle / 100;
      let newCardsWidth = cardsRatio*box['width'] - 20;
      let cardsScale = newCardsWidth / cardsWidth;
      dojo.style('construction-cards-container-resizable', 'transform', `scale(${cardsScale})`);
      dojo.style('construction-cards-container-resizable', 'width', `${cardsWidth}px`);
      dojo.style('construction-cards-container-sticky', 'height', `${cardsHeight * cardsScale}px`);
      dojo.style('construction-cards-container', 'width', `${newCardsWidth - 10}px`);

      let plansWidth = 236;
      let plansHeight = 964;
      let plansRatio = (100 - this._secondHandle) / 100;
      let newPlansWidth = plansRatio*box['width'] - 10;
      let plansScale = newPlansWidth / plansWidth;
      dojo.style('plan-cards-container-resizable', 'transform', `scale(${plansScale})`);
      dojo.style('plan-cards-container-resizable', 'width', `${plansWidth}px`);
      dojo.style('plan-cards-container-sticky', 'height', `${plansHeight * plansScale}px`);
      dojo.style('plan-cards-container', 'width', `${newPlansWidth - 20}px`);
    },


    resizeVertical(){
      let box = $('welcometo-container').getBoundingClientRect();

      let sheetWidth = 1544;
      let sheetScale = box['width'] / sheetWidth;
      dojo.style("player-score-sheet-resizable", "transform", `scale(${sheetScale})`);
      dojo.style("player-score-sheet", "width", `${box['width']}px`);
      dojo.style("player-score-sheet", "height", `${box['width']}px`);


      let cardsWidth = 1289;
      let plansWidth = 654;
      let cardsHeight = 312;

      if(this._merged){
        let totalWidth = cardsWidth + plansWidth;
        let cardsScale = (box['width'] - 40) / totalWidth;
        dojo.style('construction-cards-container', 'width', `${cardsWidth * cardsScale}px`);
        dojo.style('construction-cards-container-resizable', 'width', `${cardsWidth}px`);
        dojo.style('construction-cards-container-resizable', 'transform', `scale(${cardsScale})`);
        dojo.style('construction-cards-container-sticky', 'width', `${cardsWidth * cardsScale}px`);
        dojo.style('construction-cards-container-sticky', 'height',`${cardsHeight * cardsScale}px`);

        dojo.style('plan-cards-container', 'width', `${plansWidth * cardsScale}px`);
        dojo.style('plan-cards-container-resizable', 'width', `${plansWidth}px`);
        dojo.style('plan-cards-container-resizable', 'transform', `scale(${cardsScale})`);
        dojo.style('plan-cards-container-sticky', 'height', `${cardsHeight * cardsScale}px`);
        dojo.style('plan-cards-container-sticky', 'width', `${plansWidth * cardsScale}px`);
      }
    },
  });
});
