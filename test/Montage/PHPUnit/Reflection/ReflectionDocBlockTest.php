<?php
namespace Montage\Test\PHPUnit;

use PHPUnit\FrameworkTestCase;

use Montage\Reflection\ReflectionDocBlock;

class ReflectionFileTest extends FrameworkTestCase {

  public function testParseSimple(){
  
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
      'in' => '/** 
      *
      *
      *       
      */',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/** */',
      'out' => array()
    );
    
  
    foreach($test_list as $i => $test_map){
    
      $rdc = new ReflectionDocBlock($test_map['in']);
      
      ///$this->assertEquals($test_map['out'],$rfile->getClasses(),$i);
    
    }//foreach
  
  }//method

}//class
