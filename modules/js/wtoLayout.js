var isDebug = window.location.host == 'studio.boardgamearena.com' || window.location.hash.indexOf('debug') > -1;
var debug = isDebug ? console.info.bind(window.console) : function () { };

define(["dojo", "dojo/_base/declare",
  g_gamethemeurl + "modules/js/vendor/nouislider.min.js",
], function (dojo, declare, noUiSlider) {
  let HORIZONTAL = 0;
  let VERTICAL = 1;

  let MERGED = 0;
  let STACKED = 1;
  let STACKED_BOTTOM = 2;

  function getDefaultMode(){
    const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0)
    const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0)
    return vw > 1.1*vh? HORIZONTAL : VERTICAL;
  }

  return declare("welcometo.layout", null, {
/*********************************
********* Layout Manager *********
*********************************/
    constructor() {
      debug("Seting up the layout manager");
      this._isStandard = true;

      this._firstHandle = this.getConfig('welcometoFirstHandle', 20);
      this._secondHandle = this.getConfig('welcometoSecondHandle', 90);
      this._scoreSheetZoom = this.getConfig('welcometoScoreSheetZoom', 100);
      this._mergedMode = this.getConfig('welcometoMergedMode', MERGED);
      this._mode = this.getConfig("welcometoLayout", getDefaultMode());
    },

    reset(){
      this._ratioSlider.noUiSlider.set([20, 90]);
      this._zoomSlider.noUiSlider.set(100);
      this.setMergedMode(MERGED);
      this.setHandles(20,90);
      this.setScoreSheetZoom(100);
      this.setMode(getDefaultMode(), true);
    },

    init(isStandard){
      dojo.connect($('layout-settings'), 'onclick', () => this.toggleControls() );
      dojo.connect($('layout-control-0'), 'onclick', () => this.setMode(HORIZONTAL, true) );
      dojo.connect($('layout-control-1'), 'onclick', () => this.setMode(VERTICAL, true) );
      dojo.connect($('layout-reset'), 'onclick', () => this.reset() );

      this._isStandard = isStandard;
      dojo.attr("construction-cards-container", "data-standard", this._isStandard? 1 : 0);

      this.setMode(this._mode, false);
      this.setMergedMode(this._mergedMode);

      dojo.query("#layout-controls-container input[type=radio]").connect("click", (ev) => this.setMergedMode(ev.target.value) );

      /*
       * Double slider to choose the ratios
       */
      this._ratioSlider = document.getElementById('layout-control-ratios-range');
      noUiSlider.create(this._ratioSlider, {
        start: [this._firstHandle, this._secondHandle],
        step:1,
        margin:40,
        padding:5,
        range: {
          'min': [0],
          'max': [100]
        },
      });
      this._ratioSlider.noUiSlider.on('slide', (arg) => this.setHandles(parseInt(arg[0]), parseInt(arg[1])) );

      /*
       * Simple slider to show the zoom of scoresheet
       */
      this._zoomSlider = document.getElementById('layout-control-scoresheet-zoom-range');
      noUiSlider.create(this._zoomSlider, {
        start: [this._scoreSheetZoom],
        step:1,
        padding:5,
        range: {
          'min': [0],
          'max': [105]
        },
      });
      this._zoomSlider.noUiSlider.on('slide', (arg) => this.setScoreSheetZoom(parseInt(arg[0])) );
    },



    getConfig(value, v){
      return localStorage.getItem(value) == null? v : localStorage.getItem(value);
    },

    setMode(mode, writeInStorage){
      this._mode = mode;
      if(writeInStorage)
        localStorage.setItem('welcometoLayout', mode);

      dojo.attr("overall-content", "data-mode", mode);
      if($("layout-controls-container"))
        dojo.attr("layout-controls-container", "data-mode", mode);
      dojo.attr("welcometo-container", "data-mode", mode);
      this.onScreenWidthChange();
    },

    toggleControls(){
      dojo.toggleClass('layout-controls-container', 'layoutControlsHidden')

      // Hacking BGA framework
      if(dojo.hasClass("ebd-body", "mobile_version")){
        dojo.query(".player-board").forEach(elt => {
          if(elt.style.height != "auto"){
            dojo.style(elt, "min-height", elt.style.height);
            elt.style.height = "auto";
          }
        });
      }
    },

    setHandles(a,b){
      this._firstHandle = a;
      this._secondHandle = b;
      localStorage.setItem("welcometoLayout", HORIZONTAL);
      localStorage.setItem("welcometoFirstHandle", a);
      localStorage.setItem("welcometoSecondHangle", b);
      this.onScreenWidthChange();
    },

    setScoreSheetZoom(a){
      this._scoreSheetZoom = a;
      localStorage.setItem("welcometoLayout", HORIZONTAL);
      localStorage.setItem("welcometoScoreSheetZoom", a);
      this.onScreenWidthChange();
    },

    setMergedMode(a){
      this._mergedMode = a;
      localStorage.setItem("welcometoMergedMode", a);
      dojo.attr("welcometo-container", "data-merged", a);
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
      let firstHandle = this._isStandard? this._firstHandle : 0.6*this._firstHandle;

      let sheetWidth = 1544;
      let sheetZoom = this._scoreSheetZoom / 100;
      let sheetRatio =  (this._secondHandle - firstHandle) / 100;
      let newSheetWidth = sheetZoom*sheetRatio*box['width'];
      let sheetScale = newSheetWidth / sheetWidth;
      dojo.style("player-score-sheet-resizable", "transform", `scale(${sheetScale})`);
      dojo.style("player-score-sheet", "width", `${newSheetWidth}px`);
      dojo.style("player-score-sheet", "height", `${newSheetWidth}px`);

      let cardsWidth = this._isStandard? 433 : 208;
      let cardsHeight = 963;
      let cardsRatio = firstHandle / 100;
      let newCardsWidth = cardsRatio*box['width'] - 30;
      let cardsScale = newCardsWidth / cardsWidth;
      dojo.style('construction-cards-container-resizable', 'transform', `scale(${cardsScale})`);
      dojo.style('construction-cards-container-resizable', 'width', `${cardsWidth}px`);
      dojo.style('construction-cards-container-sticky', 'height', `${cardsHeight * cardsScale}px`);
      dojo.style('construction-cards-container-sticky', 'width', `${newCardsWidth}px`);
      dojo.style('construction-cards-container', 'width', `${newCardsWidth}px`);

      let plansWidth = 236;
      let plansHeight = 964;
      let plansRatio = 1 - sheetRatio - cardsRatio;
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


      let cardsWidth = this._isStandard? 1289 : 900;
      let plansWidth = 654;
      let cardsHeight = 312;

      if(this._mergedMode == MERGED){
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
      else {
        let cardsScale = (box['width'] - 20) / cardsWidth;
        dojo.style('construction-cards-container', 'width', `${cardsWidth * cardsScale}px`);
        dojo.style('construction-cards-container-resizable', 'width', `${cardsWidth}px`);
        dojo.style('construction-cards-container-resizable', 'transform', `scale(${cardsScale})`);
        dojo.style('construction-cards-container-sticky', 'width', `${cardsWidth * cardsScale}px`);
        dojo.style('construction-cards-container-sticky', 'height',`${cardsHeight * cardsScale}px`);

        let plansScale = 0.7 * (box['width'] - 20) / plansWidth;
        dojo.style('plan-cards-container', 'width', `${box['width']}px`);
        dojo.style('plan-cards-container-resizable', 'width', `${plansWidth}px`);
        dojo.style('plan-cards-container-resizable', 'transform', `scale(${plansScale})`);
        dojo.style('plan-cards-container-sticky', 'height', `${cardsHeight * plansScale}px`);
        dojo.style('plan-cards-container-sticky', 'width', `${plansWidth * plansScale}px`);
      }
    },
  });
});
