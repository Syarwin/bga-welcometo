<?php
namespace WTO\Helpers;

abstract class Utils extends \APP_DbObject
{
  public static function filter(&$data, $filter)
  {
    $data = array_values(array_filter($data, $filter));
  }

  public static function die($args=null){
    if(is_null($args)) throw new \BgaVisibleSystemException(implode("<br>", self::$logmsg));
    throw new \BgaVisibleSystemException(json_encode($args));
  }
}
