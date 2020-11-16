<?php
namespace WTO\Actions;

/*
 * PermitRefusal
 */
class PermitRefusal extends Zone
{
  protected static $type = "permit-refusal";
  protected static $dim = 1;
  protected static $cols = 3;

  protected static $scores = [0, 0, 3, 5];
  public function getScore($player)
  {
    $free = count(self::$scores) - 1;
    foreach(self::getAvailableZones($player) as $zone)
      $free = $zone[0];

    return ['permit-total' => self::$scores[$free] ];
  }
}
