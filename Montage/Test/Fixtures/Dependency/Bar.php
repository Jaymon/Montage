<?php

namespace Montage\Test\Fixtures\Dependency;

class Bar extends Foo {

  public $che = null;

  public function __construct(\Che $che){
  
    $this->che = $che;
  
  }//method
  
}//class
