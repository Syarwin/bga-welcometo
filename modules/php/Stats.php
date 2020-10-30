<?php
namespace WTO;
use welcometo;

class Stats extends \APP_DbObject
{
  protected static function init($type, $name, $value = 0){
    welcometo::get()->initStat($type, $name, $value);
  }

  protected static function inc($name, $player = null, $value = 1){
    welcometo::get()->incStat($value, $name, $player);
  }

  protected static function get($name, $player = null){
    welcometo::get()->getStat($name, $player);
  }

  protected static function set($value, $name, $player = null){
    welcometo::get()->setStat($value, $name, $player);
  }


  public static function setupNewGame(){
    self::init('table', 'turns_number');
    self::init('table', 'permit_refusal_ending');
    self::init('table', 'projects_ending');
    self::init('table', 'all_houses_ending');

    self::init('player', 'houses_opened_number');
    self::init('player', 'permit_refusal_number');
    self::init('player', 'projects_number');
    self::init('player', 'no_effect_number');
    self::init('player', 'surveyor_number');
    self::init('player', 'real_estate_number');
    self::init('player', 'landscaper_number');
    self::init('player', 'pool_manufacturer_number');
    self::init('player', 'temp_agency_number');
    self::init('player', 'bis_number');
  }


/*
  public static function newTurn($pId){
    self::inc('table_turns_number');
    self::inc('player_turns_number', $pId);
  }


  public static function payTaxes($player, $taxer, $cost){
    self::inc('player_money_paid', $player['id'], $cost);
    self::inc('player_money_earned', $taxer['id'], $cost);

    $tableHighestPaid = self::get('table_highest_taxes_collected');
    if ($tableHighestPaid < $cost)
      self::set($cost, 'table_highest_taxes_collected');

    $taxerHighestPaid = self::get('player_highest_taxes_collected', $taxer['id']);
    if ($taxerHighestPaid < $cost)
      self::set($cost, 'player_highest_taxes_collected', $taxer['id']);
  }

  public static function placeCarpet($pId, $type, $pos){
    $currentZone = Board::getTaxesZone($pId, $type, $pos);
    $size = count($currentZone);

    $tableLargestZone = self::get('table_largest_carpet_zone');
    if ($tableLargestZone < $size )
      self::set($size, 'table_largest_carpet_zone');

    $playerLargestZone = self::get('player_largest_carpet_zone', $pId);
    if ($playerLargestZone < $size)
      self::set($size, 'player_largest_carpet_zone', $pId);
  }
*/
}

?>
