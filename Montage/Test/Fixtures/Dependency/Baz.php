<?php

class Baz {

  public $one = '';
  public $two = null;
  public $three = null;

  public function __construct($one,$two,$three = 0){
  
    $this->one = $one;
    $this->two = $two;
    $this->three = $three;
  
  }//method

}//class
