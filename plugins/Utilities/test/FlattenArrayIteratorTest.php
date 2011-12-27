<?php

///use FlattenArrayIterator;

$base = realpath(__DIR__.'/../src');
require_once($base.'/FlattenArrayIterator.php');

class FlattenArrayIteratorTest extends \PHPUnit_Framework_TestCase {

  public function testSimpleArray(){
  
    $arr = array(1,2,3,4,5);
    
    $iterator = new FlattenArrayIterator($arr);

    $count = 0;

    foreach($iterator as $row){
    
      $count++;
    
    }//foreach
    
    $this->assertEquals(5,$count);
  
  }//method
  
  /**
   *  tests to make sure it can flatten real depth arrays, I added this test because
   *  it turns out my original tests never went more than 1 array deep, sigh
   *  
   *  @since  12-22-11
   */
  public function testMultiAssocArray(){
  
    $arr = array(
      'foo' => array(
        'bar' => array(
          'a' => 1,
          'b' => 2,
          'c' => 3,
          'd' => 4
        )
      ),
      'bar' => array(
        'foo' => array(
          'baz' => array(
            'e' => 5,
            'f' => 6,
            'g' => array(7,8),
            'h' => 9
          ),
          'che' => 10
        )
      )
    );
  
    $iterator = new FlattenArrayIterator($arr);

    $count = 0;

    foreach($iterator as $key => $row){
    
      ///\out::e($key,$row);
    
      $count++;
    
    }//foreach
  
    $this->assertEquals(10,$count);
  
  }//method
  
  public function testMultiArray(){
  
    $arr = array(1,2,array(3,4,array(5,6),7),8,9,array(10,new ArrayIterator(array(11,12))),13);
    
    $iterator = new FlattenArrayIterator($arr);

    $count = 0;

    foreach($iterator as $key => $row){
    
      ///\out::e($key,$row);
    
      $count++;
    
    }//foreach
  
    $this->assertEquals(12,$count);
  
  }//method
  
}//class
