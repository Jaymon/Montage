<?php

class Baz {

  public $one = '';
  public $two = null;
  public $three = 0;

  public function __construct($one,$two,$three = null){
  
    $this->one = $one;
    $this->two = $two;
    $this->three = $three;
  
  }//method

}//class
