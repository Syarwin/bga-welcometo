<?php
namespace WTO\Actions;

/*
 * Temp : manage everything related to temp agency
 */
class Temp extends Zone
{
  protected static $type = "score-temp";
  protected static $dim = 1;
  protected static $cols = 11;

  public function getScores($pId)
  {

  }
}
