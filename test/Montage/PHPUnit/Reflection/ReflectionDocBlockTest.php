<?php
namespace Montage\Test\PHPUnit;

use PHPUnit\FrameworkTestCase;

use Montage\Reflection\ReflectionDocBlock;

class ReflectionFileTest extends FrameworkTestCase {

  public function testParse(){
  
    /* 
    $test_map_prototype = array(
      'in' => '<'.'?php',
      'out' => array(
        0 => array(
          'class' => '',
          'extends' => array(),
          'implements' => array()
        )
      )
    );
    */
  
    $test_list = array();
    $test_list[] = array(
      'in' => '/'.'** 
      * this will be the multi-line long description
      * and here is the second line
      * and the third            
      *'.'/',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/'.'** 
      * this will be the short description
      *'.'/',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/'.'** 
      *
      *
      *       
      *'.'/',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/'.'** *'.'/',
      'out' => array()
    );
    
  
    foreach($test_list as $i => $test_map){
    
      $rdc = new ReflectionDocBlock($test_map['in']);
      return;
      
      ///$this->assertEquals($test_map['out'],$rfile->getClasses(),$i);
    
    }//foreach
  
  }//method

}//class
