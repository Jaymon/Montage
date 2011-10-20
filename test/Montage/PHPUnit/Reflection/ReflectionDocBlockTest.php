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
      * this is the variable description
      * 
      * @var  \Path\To\The\Class                              
      ********************************************************************'.'/',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/'.'**
      * this is the variable description
      * 
      * @var  \Path\To\The\Class                              
      *'.'/',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/'.'**
      * 
      * @param  boolean $one  this is a mutli-line description
      *                       that has some more text
      *                       
      * and a little more text                  
      * 
      * @param  string $two  description                  
      *'.'/',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/'.'**
      * 
      * @param  boolean $one  this is a mutli-line description
      *                       that has some more text      
      * 
      * @param  string $two  description                  
      *'.'/',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/'.'**
      * this is the short description
      * 
      * this is the long description.
      * and this also.
      * 
      * and also this.
      *                      
      * @param  boolean $one  this is a mutli-line description
      *                       that has some more text      
      * @param  string $two  description                  
      *'.'/',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/'.'**
      * this is the short description
      * 
      * this is the long description.
      * and this also.
      * 
      * and also this.
      *                      
      * @param  boolean $one  description
      * @param  string $two  description                  
      *'.'/',
      'out' => array()
    );
    $test_list[] = array(
      'in' => '/'.'** 
      * @param  boolean $is_there true if there, false otherwise            
      *'.'/',
      'out' => array()
    );
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
      
      \out::i($rdc);
      
      ///$this->assertEquals($test_map['out'],$rfile->getClasses(),$i);
    
    }//foreach
  
  }//method

}//class
