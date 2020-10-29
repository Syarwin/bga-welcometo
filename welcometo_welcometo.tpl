{OVERALL_GAME_HEADER}

<!-- 
--------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- welcometo implementation : © Geoffrey VOYER <geoffrey.voyer@gmail.com>
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-------
-->

<div id="cards_wrap" class="whiteblock">
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

<div id="player_score_sheet_wrap">
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

<script type="text/javascript">

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

var score_sheet_div = '<div id="responsive_score_sheet" class="responsive_score_sheet">\
  <div id="score_sheet" class="score_sheet"></div>\
</div>';

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

</script>  

{OVERALL_GAME_FOOTER}
