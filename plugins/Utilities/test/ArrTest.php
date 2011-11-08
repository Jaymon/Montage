<?php

$base = realpath(__DIR__.'/../src');
require_once($base.'/Arr.php');

class ArrTest extends \PHPUnit_Framework_TestCase {

  /**
   *  @since  11-7-11
   */
  public function testCreate(){
  
    $arr = new Arr('one','two','three');
    $narr = $arr->getArrayCopy();
    $this->assertEquals(array('one','two','three'),$narr);
    
    $arr = new Arr(array('one','two','three'));
    $narr = $arr->getArrayCopy();
    $this->assertEquals(array('one','two','three'),$narr);
    
    $arr[] = 'four';
    
    \out::e($arr->getArrayCopy());
  
  }//method
  
}//class
