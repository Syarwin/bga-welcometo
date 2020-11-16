<?php
namespace WTO\Actions;
use WTO\Houses;

/*
 * TopFence : manage marking related to plan validation
 */
class TopFence extends Zone
{
  protected static $type = "top-fence";
  protected static $cols = [10,11,12];
}
