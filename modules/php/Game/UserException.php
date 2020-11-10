<?php
namespace WTO\Game;
use welcometo;

class UserException extends \BgaUserException {
  public function __construct($str)
  {
    parent::__construct(welcometo::translate($str));
  }
}
?>
