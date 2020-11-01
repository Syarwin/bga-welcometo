{OVERALL_GAME_HEADER}

<div id="cards-container" class="whiteblock">
    <h3>Table</h3>
    <div id="responsive_card_viewer">
        <div id="responsive_plan_cards">
            <div id="plan_cards_wrap"></div>
        </div>
        <div id="responsive_construction_cards">
            <div id="construction_cards_wrap_global">
                <div id="construction_cards_wrap_0" class="construction_cards_wrap"></div>
                <div id="construction_cards_wrap_1" class="construction_cards_wrap"></div>
                <div id="construction_cards_wrap_2" class="construction_cards_wrap"></div>
            </div>
        </div>
    </div>
</div>

<div id="player-score-sheet">
</div>

  <!-- TBD : One modal per player? -->
<div id="myModal" class="modal">
  <!-- Modal content -->
  <div id="myModalContent" class="modal-content">
    <span id="myCloseModal" class="close">&times;</span>
    <div id="responsive_score_sheet" class="responsive_score_sheet">
       <div id="my_score_sheet" class="score_sheet"></div>
    </div>
  </div>
</div>

<audio id="audiosrc_o_welcometo_scribble" src="{URL}/img/sound/scribble.ogg" preload="none" autobuffer></audio>
<audio id="audiosrc_welcometo_scribble" src="{URL}/img/scribble.mp3" preload="none" autobuffer></audio>

<script type="text/javascript">
var jstpl_scoreSheet = `
<div class="score-sheet-container">
  <div id="score-sheet-\${pId}" class="score-sheet"></div>
</div>`;


//// TOP PART ////
var jstpl_cityName = '<div id="${pId}_cityname" class="cityname" style="color:#${color}">${name}</div>';
var jstpl_house = `
  <div id="\${pId}_house_\${x}_\${y}" data-x="\${x}" data-y="\${y}" class="house"></div>
  <div id="\${pId}_top_fence_\${x}_\${y}" data-x="\${x}" data-y="\${y}" class="top-fence"></div>
  <div id="\${pId}_estate_fence_\${x}_\${y}" data-x="\${x}" data-y="\${y}" class="estate-fence"></div>
`;
var jstpl_park = '<div id="${pId}_park_${x}_${y}" data-x="${x}" data-y="${y}" class="park"></div>';

//// BOTTOM PART ////
var jstpl_scorePool = '<div id="${pId}_score_pool_${x}" data-x="${x}" class="score-pool"></div>';
var jstpl_scoreTemp = '<div id="${pId}_score_temp_${x}" data-x="${x}" class="score-temp"></div>';
var jstpl_scoreEstate = '<div id="${pId}_score_estate_${x}_${y}" data-x="${x}" data-y="${y}" class="score-estate"></div>';
var jstpl_scoreBis = '<div id="${pId}_score_bis_${x}" data-x="${x}" class="score-bis"></div>';







var jstpl_scribble = `
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 533.33331 533.33331" class="scribble" id="scribble-\${id}">
	<clipPath id="scribble-clip-path">
    <path
       d="m 32.923611,508.08566 c -3.525347,-1.37744 -7.275347,-3.22284 -8.333333,-4.10089 -2.500036,-2.07484 -2.493339,-13.22218 0.01999,-33.26993 2.828011,-22.55787 5.432635,-33.85848 18.285135,-79.33333 2.6775,-9.47356 7.507377,-23.96985 11.139537,-33.434 5.001867,-13.03312 5.198376,-13.51892 9.667383,-23.89933 2.210018,-5.13333 8.345854,-19.53333 13.63519,-32 9.72597,-22.92356 41.297687,-89.05874 48.374667,-101.33333 2.11404,-3.66667 4.94242,-7.56667 6.2853,-8.66667 2.49272,-2.04186 4.12886,-4.76201 12.02981,-19.99999 15.94965,-30.76096 43.05165,-71.37473 60.5651,-90.76004 4.71314,-5.21687 4.8135,-5.25389 8.20978,-3.02857 4.54054,2.97509 3.72253,7.0516 -4.08867,20.375379 -9.68825,16.525521 -15.85743,30.806451 -14.06897,32.568011 1.1476,1.13032 2.02904,0.83888 3.49258,-1.15478 1.07668,-1.46667 4.45655,-5.59476 7.51082,-9.17353 3.05425,-3.57878 11.382,-15.06358 18.50607,-25.5218 7.1241,-10.45821 21.10384,-30.00872 31.06611,-43.44556 9.96227,-13.43685 18.11321,-25.43989 18.11321,-26.67342 0,-2.879882 8.00017,-9.905978 15.33921,-13.471563 l 5.70978,-2.7740193 4.80852,2.9718303 c 8.71185,5.384223 16.80916,17.227152 16.80916,24.584692 0,3.17056 -16.11668,35.60902 -23.27027,46.8367 -2.33619,3.66667 -7.74531,13.26667 -12.02028,21.33334 -4.27497,8.06666 -9.50924,17.90059 -11.63168,21.85316 -2.12245,3.95257 -5.3444,8.35491 -7.15988,9.78297 -3.47498,2.73341 -16.00816,23.10097 -22.114,35.93726 -1.98134,4.16538 -5.44777,9.50436 -7.70317,11.86443 -4.35263,4.55461 -5.11264,7.68358 -2.50072,10.2955 2.54297,2.54298 3.39775,1.98682 7.27492,-4.73333 2.00971,-3.48333 7.585,-12.21513 12.38955,-19.40398 4.80454,-7.18887 11.22642,-16.97032 14.27086,-21.73659 14.55005,-22.77908 32.62409,-44.27258 40.47943,-48.1378 3.29188,-1.619771 6.40705,-2.969771 6.92261,-3.000001 0.51556,-0.0302 5.1534,3.695041 10.30632,8.278381 9.83107,8.74438 11.16851,11.84132 7.73665,17.91475 -0.90585,1.60311 -3.68486,6.39969 -6.17557,10.65907 -2.49072,4.25937 -9.29688,16.74773 -15.1248,27.75191 -5.82793,11.00418 -11.63813,21.2076 -12.91157,22.67426 -1.27344,1.46667 -3.03844,5.20688 -3.92222,8.31157 -0.89544,3.14571 -2.82188,6.29518 -4.35118,7.11364 -1.51308,0.80978 -5.30247,6.95112 -8.44616,13.68842 -3.136,6.72084 -8.1379,17.46313 -11.11534,23.87177 -2.97742,6.40864 -7.33277,14.32459 -9.67853,17.591 -2.66509,3.71108 -3.94565,6.77116 -3.41368,8.15747 0.49211,1.28241 -0.84194,5.55066 -3.16165,10.11553 -2.20713,4.34336 -4.01297,8.83834 -4.01297,9.98886 0,1.15051 -1.70151,3.68256 -3.78111,5.62679 -4.78809,4.47636 -9.42012,16.7552 -10.31623,27.34684 -0.6875,8.12605 -0.67461,8.16365 1.70228,4.96181 1.31728,-1.77445 6.79744,-11.67445 12.17815,-22 24.15278,-46.34907 39.65166,-73.12447 64.74716,-111.8553 9.05894,-13.981 24.97242,-36.16569 27.43866,-38.25173 0.4335,-0.36667 3.09011,-3.51667 5.90357,-7 2.81348,-3.48333 5.74588,-6.33333 6.51647,-6.33333 0.77059,0 2.34831,-0.84026 3.50605,-1.86722 1.15775,-1.02697 4.41819,-2.82277 7.24544,-3.99065 5.1285,-2.11849 5.14914,-2.11415 8.86828,1.86721 2.05031,2.19487 4.9517,3.99066 6.44752,3.99066 1.49584,0 3.08044,0.58369 3.52136,1.2971 0.44091,0.7134 3.2217,1.94875 6.17954,2.74522 6.84277,1.84256 10.40825,8.6986 7.99474,15.37304 -0.81688,2.25901 -1.48522,5.63097 -1.48522,7.49324 0,1.86226 -1.52966,5.64001 -3.39924,8.39498 -3.58103,5.27695 -3.36854,7.56703 0.47916,5.16411 3.7567,-2.34611 11.21369,-0.39963 16.82674,4.39224 2.882,2.46037 6.29659,4.4734 7.58798,4.4734 1.29138,0 2.7511,0.65227 3.24381,1.44949 0.49271,0.79722 2.0885,1.1376 3.54622,0.7564 2.53536,-0.66301 4.40048,-0.12773 13.98455,4.01361 7.40003,3.1976 12.01679,8.18219 16.94888,18.29924 2.63004,5.39492 4.78189,10.71175 4.78189,11.81518 0,2.56058 2.02076,4.00846 8.66667,6.2097 6.56744,2.17524 22.0482,11.68239 24.39133,14.97936 0.94856,1.33471 3.3681,5.58806 5.37673,9.45188 3.62408,6.97132 3.63602,7.08131 1.55826,14.35847 -2.49133,8.72568 -6.08099,17.11657 -8.53519,19.9512 -0.9756,1.12684 -2.09562,4.5988 -2.48893,7.71546 -0.67472,5.34655 -0.52677,5.66667 2.61895,5.66667 1.83374,0 5.11701,1.05 7.29615,2.33333 3.7734,2.22223 4.02446,2.22223 5.27198,0 0.72044,-1.28333 1.93745,-2.33333 2.70448,-2.33333 3.01069,0 9.29825,5.79875 11.18573,10.31613 2.795,6.68939 2.68995,12.72087 -0.43576,25.0172 -1.49131,5.86667 -3.12843,13.36667 -3.63804,16.66667 -1.22296,7.91902 -4.63264,21.42521 -6.12099,24.24604 -0.69233,1.31212 -0.68921,3.55026 0.008,5.38273 2.4392,6.41559 -4.8344,18.57733 -13.79859,23.07179 -2.04566,1.02565 -2.90014,3.07996 -3.42019,8.22273 -0.42,4.15328 -1.73388,8.01837 -3.33333,9.8057 -3.54668,3.96332 -11.09199,8.62655 -15.86819,9.80702 -2.14212,0.52944 -5.29981,2.06784 -7.01709,3.41865 -4.40942,3.46844 -18.73147,2.34541 -21.96779,-1.72255 -2.21213,-2.78057 -2.21493,-2.78064 -7.16608,-0.17356 -2.72419,1.43447 -6.45808,4.85811 -8.29752,7.60811 -1.83944,2.75 -3.86471,5 -4.5006,5 -2.47082,0 -0.88,-6.2532 2.88323,-11.33333 6.73002,-9.08514 9.98078,-18 6.56362,-18 -1.71637,0 -6.85896,5.43806 -12.34625,13.05564 -4.82841,6.70289 -10.2574,10.34968 -17.37176,11.66904 -4.8365,0.89693 -5.09058,0.77598 -4.66666,-2.22139 0.2466,-1.74348 1.79834,-3.76224 3.44834,-4.48613 1.94075,-0.85146 3.01668,-2.49918 3.04726,-4.66667 0.026,-1.84277 0.90472,-4.85049 1.95274,-6.68383 1.04803,-1.83333 1.92678,-4.08333 1.95275,-5 0.0649,-2.29123 -3.26837,-2.09786 -5.26283,0.30532 -1.2717,1.53231 -2.24729,1.64515 -4.37562,0.5061 -1.54218,-0.82535 -5.48196,-1.14103 -9.01628,-0.72244 -7.1702,0.84921 -10.814,-0.99623 -13.95318,-7.06673 -1.0853,-2.09874 -4.38188,-6.5988 -7.32572,-10.00015 -2.94436,-3.40136 -5.35296,-7.40644 -5.35296,-8.90019 0,-1.49374 2.50535,-7.7719 5.56744,-13.95146 4.22058,-8.51747 5.19363,-11.60938 4.02223,-12.78078 -0.84988,-0.84988 -1.74717,-1.28522 -1.99401,-0.96744 -6.14226,7.90755 -15.25902,23.23878 -23.92075,40.22636 -8.8907,17.43667 -11.89852,22.1292 -16.1657,25.22033 -6.68191,4.84035 -12.84254,5.1591 -12.84254,0.66447 0,-2.9786 -6.21858,-12.94171 -9.24526,-14.81229 -2.72283,-1.6828 -1.37758,-8.03345 5.36377,-25.32108 10.61128,-27.2118 33.37821,-81.09316 47.96121,-113.50744 5.60068,-12.44888 7.98925,-19.69026 6.86551,-20.81401 -2.07295,-2.07294 -8.06167,1.30032 -10.41092,5.86416 -1.24306,2.41484 -5.0631,8.59061 -8.489,13.72395 -3.4259,5.13333 -9.69109,15.93333 -13.92265,24 -4.23158,8.06666 -9.0207,16.46666 -10.64251,18.66666 -1.6218,2.2 -5.00004,8.8 -7.5072,14.66667 -2.50715,5.86667 -6.93475,14.86666 -9.83909,20 -2.90436,5.13333 -6.87392,12.93333 -8.82124,17.33333 -1.94732,4.4 -6.26535,13.7 -9.5956,20.66667 -3.33026,6.96666 -9.22624,19.77221 -13.10218,28.45677 -7.75837,17.38368 -15.06497,28.50986 -19.48925,29.6774 -5.73212,1.51266 -15.12558,-8.39415 -15.12558,-15.9522 0,-3.36455 10.54104,-34.65245 18.70337,-55.51531 2.1518,-5.5 7.13725,-19 11.07877,-30 6.35083,-17.72393 17.69943,-47.28327 20.98795,-54.66666 7.71549,-17.32284 25.2299,-60.06272 25.2299,-61.56774 0,-6.29778 -10.25589,4.3232 -18.89514,19.56774 -2.28574,4.03334 -6.19019,10.93334 -8.67658,15.33333 -2.48637,4.4 -6.92017,12.2 -9.85289,17.33334 -7.63499,13.36406 -31.9433,62.95463 -38.56084,78.66666 -3.08862,7.33333 -6.43573,15.13333 -7.43802,17.33333 -3.06978,6.73808 -9.90044,23.94282 -9.90528,24.94894 -0.003,0.52192 -3.85646,10.12192 -8.5643,21.33333 -4.70785,11.21142 -12.53337,30.5844 -17.39004,43.05106 -9.95247,25.54715 -18.07398,39.33333 -23.17147,39.33333 -8.60503,0 -20.22398,-10.1903 -20.19635,-17.71306 0.0293,-7.99276 11.90093,-50.0466 16.75384,-59.34875 1.03436,-1.98266 1.88435,-4.64792 1.88887,-5.92278 0.0333,-9.39496 39.7863,-115.40656 63.62865,-169.68206 20.98357,-47.76772 22.31416,-51.33333 19.15622,-51.33333 -3.40946,0 -37.11002,58.04048 -41.4189,71.33333 -0.95084,2.93333 -4.38771,10.65573 -7.63747,17.16089 -3.24976,6.50515 -8.62623,18.20515 -11.94769,26 -5.89833,13.84225 -8.21263,19.11253 -14.47476,32.96314 -1.68863,3.73488 -3.97508,9.83768 -5.08101,13.56178 -1.10594,3.72408 -3.85715,10.76013 -6.11382,15.63565 -2.25665,4.87552 -4.10301,9.18204 -4.10301,9.57005 0,0.38802 -0.96117,2.89615 -2.13595,5.57364 -1.17477,2.6775 -2.64892,6.06818 -3.27588,7.53484 -13.84848,32.39608 -22.26306,47.11556 -31.92347,55.84316 -8.10036,7.31819 -11.33474,6.72243 -22.67436,-4.17649 l -9.363897,-9 1.55327,-4.66667 c 0.85429,-2.56667 3.91754,-10.66667 6.807207,-18 2.88966,-7.33333 8.44829,-21.43333 12.35251,-31.33333 3.90421,-9.9 10.21816,-26.4 14.031,-36.66667 3.81283,-10.26666 9.33064,-23.76666 12.26177,-29.99999 2.93113,-6.23334 7.30041,-16.13334 9.70949,-22 2.40908,-5.86667 8.56852,-19.96667 13.68766,-31.33334 5.11913,-11.36666 13.31553,-29.66666 18.21422,-40.66666 4.89871,-11 18.75344,-39.32663 30.78831,-62.94805 12.03486,-23.62143 21.31641,-43.29734 20.62566,-43.72425 -0.69074,-0.4269 -4.12126,3.18763 -7.62338,8.0323 -6.8623,9.49298 -26.17026,43.67166 -28.98376,51.30667 -0.94583,2.56667 -2.93996,6.76666 -4.43143,9.33333 -1.49145,2.56667 -3.62197,7.36667 -4.73446,10.66667 -1.11251,3.3 -6.33103,14.99116 -11.59672,25.98036 -5.2657,10.9892 -10.64396,22.6892 -11.95171,26 -1.30773,3.3108 -3.86681,9.31963 -5.68685,13.35297 -3.57047,7.91244 -13.99788,32.92126 -17.78984,42.66666 -1.28404,3.3 -3.08895,7.2 -4.01091,8.66667 -0.92196,1.46667 -3.27462,7.44999 -5.22815,13.29628 -1.95353,5.84628 -4.65111,13.04628 -5.99462,16 -1.34351,2.9537 -3.42425,8.37038 -4.62386,12.03705 -2.13899,6.53789 -12.65726,32.16631 -18.60547,45.33333 -1.65642,3.66667 -5.825467,13.26667 -9.264557,21.33333 -3.43909,8.06667 -10.0392,22.16667 -14.666898,31.33334 -23.943206,47.42729 -25.20749,49.25058 -34.131583,49.22301 -2.566666,-0.008 -7.551041,-1.14142 -11.076387,-2.51886 z M 473.02321,416.00707 c 0.21294,-1.10576 -0.4935,-3.19887 -1.56987,-4.65135 -1.81184,-2.44493 -2.12115,-2.47929 -4.16869,-0.46309 -1.79802,1.77047 -1.92111,2.72067 -0.6582,5.08044 1.70989,3.19494 5.78385,3.21661 6.39676,0.034 z m 33.3171,-92.62556 c -0.48904,-2.55826 -0.0784,-3.33333 1.76587,-3.33333 3.31661,0 3.92305,-1.45329 2.99438,-7.17593 -0.80165,-4.94004 -5.00424,-8.84508 -7.11822,-6.61426 -0.58558,0.61794 -0.63922,3.52352 -0.1192,6.45686 0.52001,2.93333 0.36884,6.30385 -0.33594,7.49004 -1.48929,2.50661 -0.30874,6.50996 1.91971,6.50996 0.91369,0 1.27379,-1.34352 0.89341,-3.33334 z M 212.06763,229.81728 c 1.79613,-3.427 4.87767,-9.277 6.84785,-13 3.8189,-7.21644 4.52899,-10.10243 2.4857,-10.10243 -2.10734,0 -7.46894,8.20283 -10.69079,16.35605 -1.6712,4.22916 -3.56472,8.01458 -4.20781,8.41202 -1.80754,1.11712 -1.38074,4.56526 0.56505,4.56526 0.95388,0 3.20388,-2.8039 5,-6.2309 z"
	/>
</clipPath>
<path
	 clip-path="url(#scribble-clip-path)"
	 fill="none" stroke-miterlimit="0" stroke-width="70"
   class="scribble-path"
   d="M 207.55019,87.389555 C 76.569696,311.99391 57.909342,399.75673 38.554216,493.49396 122.0427,330.8991 181.21334,138.53703 294.29718,30.200802 229.2164,163.7014 169.67116,281.85814 115.02007,442.73091 174.01295,312.46209 233.33432,199.24878 308.43372,114.37751 281.67061,154.27485 201.50136,359.17532 169.63854,487.71083 234.96652,374.61846 264.42725,235.62198 365.62247,148.43373 l -114.3775,286.58633 c 47.33601,-82.46318 79.17712,-192.04244 142.00803,-247.38955 4.53817,74.75234 -48.6109,151.43239 -71.96787,226.18473 37.9056,-68.3347 65.46371,-150.46604 113.73493,-204.97991 -13.56619,75.03628 -37.51108,129.31518 -57.18875,192.12851 l 93.81526,-152.28915 -53.97591,170.28112 73.25302,-104.09638 c 4.77407,41.49757 -20.97985,75.72064 -35.98394,110.52208"
 />
</svg>
`;
/*
var jstpl_house = '<div id="house_${pId}_${x}_${y}" data-x="${x}" data-y="${y}" class="house"></div>;
var jstpl_topFence = ';
var jstpl_rightFence = '';
*/

//// BOTTOM PART ////
/*
var jstpl_scorePlans = '<div id="score_${pId}_plan_${plan_id}" class="plan-score"></div>';
var park_score_div = '<div id="park_${street}_score_${player_id}" class="park_score_div park_${street}_score handwritten"></div>';
var pool_score_div = '<div id="pool_${pool_number}_score_${player_id}" class="pool_score_div pool_line_${pool_line} pool_column_${pool_column}"></div>';
var real_estate_score_div = '<div id="real_estate_${size}_${number}_score_${player_id}" class="real_estate_score_div real_estate_size_${size} real_estate_${size}_${number}"></div>';
var temp_score_div = '<div id="temp_${temp_number}_score_${player_id}" class="temp_score_div temp_line_${temp_line} temp_column_${temp_column}"></div>';
var bis_score_div = '<div id="bis_${bis_number}_score_${player_id}" class="bis_score_div bis_line_${bis_line} bis_column_${bis_column}"></div>';
var roundabout_score_div = '<div id="roundabout_${roundabout_number}_score_${player_id}" class="roundabout_score_div roundabout_${roundabout_number}_score"></div>';
var permit_refusal_score_div = '<div id="permit_refusal_${permit_refusal_number}_score_${player_id}" class="permit_refusal_score_div permit_refusal_${permit_refusal_number}_score"></div>';

var result_score_div = '<div id="result_score_${category}_${player_id}" class="result_score_div result_score_${category} handwritten"></div>';
var real_estate_score_number_div = '<div id="real_estate_${size}_number_${player_id}" class="real_estate_score_number_div real_estate_number_size_${size} handwritten"></div>';
var temp_ranking_score_div = '<div id="temp_ranking_${rank}_score_${player_id}" class="temp_ranking_score_div temp_ranking_${rank}_score_div"></div>';

*/

/*
var jstpl_player_board = '\
<span id="player_${id}_helper_${icon}">\
    <img id="player_${id}_icon_${icon}" class=${icon}></img>\
</span>';

var player_board_modal = '<div id="modal_${player_id}" class="modal">\
  <div id="modal_content_${player_id}" class="modal-content">\
    <span id="close_modal_${player_id}" class="close">&times;</span>\
  </div>\
</div>';

var last_turn_modal = '<div id="modal_${player_id}_last_turn" class="modal">\
  <div id="modal_content_${player_id}_last_turn" class="modal-content">\
    <span id="close_modal_${player_id}_last_turn" class="close">&times;</span>\
  </div>\
</div>';

var last_turn_content = '<div id="modal_${player_id}_last_turn_wrapper">\
${content_blocks}\
</div>';

var modal_template = '<div id="modal_${player_id}_${modal_name}" class="modal modal_${modal_name}">\
  <div id="modal_content_${player_id}_${modal_name}" class="modal-content modal-content-${modal_name}">\
    <span id="close_modal_${player_id}_${modal_name}" class="close">&times;</span>\
  </div>\
</div>';

var easy_opening_modal = '<div id="easy_opening_modal"></div>';
var easy_opening_choice = '<button id="easy_opening_${number}" class="easy_opening_button">${number}</button>';

var bis_modal = '<div id="bis_modal"></div>';
var bis_choice = '<button id="easy_opening_${direction}" class="easy_opening_button">Copy from ${direction} : ${number}</button>';

var house_div = '<div id="house_${house_id}_${player_id}" class="avenue${avenue} house${house_id} house_div empty_house handwritten"></div>';
var house_pool_div = '<div id="house_pool_${house_id}_${player_id}" class="avenue${avenue} pool${street} house_pool_div handwritten"></div>';
var plan_fence_div = '<div id="top_fence_${house_id}_${player_id}" class="avenue${avenue} street${street} plan_fence_div"></div>';
var estate_fence_div = '<div id="estate_fence_${house_id}_${player_id}" class="avenue${avenue} street${street} estate_fence_div"></div>';
var park_div = '<div id="park_${street}_${park_number}_${player_id}" class="street${street} park_column${park_column} park_div handwritten"></div>';


var plan_score_div = '<div id="plan_${plan_id}_score_${player_id}" class="plan_score_div plan_${plan_id}_score handwritten"></div>';
var park_score_div = '<div id="park_${street}_score_${player_id}" class="park_score_div park_${street}_score handwritten"></div>';
var pool_score_div = '<div id="pool_${pool_number}_score_${player_id}" class="pool_score_div pool_line_${pool_line} pool_column_${pool_column}"></div>';
var real_estate_score_div = '<div id="real_estate_${size}_${number}_score_${player_id}" class="real_estate_score_div real_estate_size_${size} real_estate_${size}_${number}"></div>';
var temp_score_div = '<div id="temp_${temp_number}_score_${player_id}" class="temp_score_div temp_line_${temp_line} temp_column_${temp_column}"></div>';
var bis_score_div = '<div id="bis_${bis_number}_score_${player_id}" class="bis_score_div bis_line_${bis_line} bis_column_${bis_column}"></div>';
var roundabout_score_div = '<div id="roundabout_${roundabout_number}_score_${player_id}" class="roundabout_score_div roundabout_${roundabout_number}_score"></div>';
var permit_refusal_score_div = '<div id="permit_refusal_${permit_refusal_number}_score_${player_id}" class="permit_refusal_score_div permit_refusal_${permit_refusal_number}_score"></div>';

var result_score_div = '<div id="result_score_${category}_${player_id}" class="result_score_div result_score_${category} handwritten"></div>';
var real_estate_score_number_div = '<div id="real_estate_${size}_number_${player_id}" class="real_estate_score_number_div real_estate_number_size_${size} handwritten"></div>';
var temp_ranking_score_div = '<div id="temp_ranking_${rank}_score_${player_id}" class="temp_ranking_score_div temp_ranking_${rank}_score_div"></div>';
*/
</script>

{OVERALL_GAME_FOOTER}
