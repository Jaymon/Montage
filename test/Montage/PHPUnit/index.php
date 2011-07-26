<?php

namespace foo {

  use bar\che as bc;
  // use bc\baz; // not valid, that's what I wanted to know
  
  class a {
  
    public function doh(){
    
      $baz = new baz();
      echo get_class($baz),PHP_EOL;
    
    }//method
    
    public function same($a){
    
      return ($a instanceof self);
    
    }//method
  
  
  }//class

}//namespace

namespace bar\che {

  class baz {
  
    public function get($bit = ''){
    
      $args = func_get_args();
      \out::e($args);
    
    }//method
  
  }//class

}//namespace

namespace {

  include('out_class.php');

  ///echo class_exists('\FOO\A') ? 'TRUE' : 'FALSE',PHP_EOL;

  ///$ra = new ReflectionClass('\FOO\A');
  ///echo $ra->getName(),PHP_EOL;

  $a = new \foo\a();
  $aa = new \foo\a();
  $b = new \bar\che\baz();
  $b->get('blah');
  
  echo $a->same($aa) ? 'TRUE' : 'FALSE';
  echo $a->same($b) ? 'TRUE' : 'FALSE';
  ///$a->doh();

}//namespace
