<?php
namespace Montage\Test\PHPUnit;

use PHPUnit\FrameworkTestCase;

use Montage\Reflection\ReflectionDocBlock;

class ReflectionDocBlockTest extends FrameworkTestCase {

  public function testParse(){

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
  
  /**
   *  test parsing certain supported tags
   *  
   *  tags are parsed according to this list: http://en.wikipedia.org/wiki/PHPDoc#Tags      
   *
   *  @since  10-31-11   
   */
  public function testGetParsedTag(){
  
    $test_list = array();
    $test_list['one full param'] = array(
      'in' => '/'.'**
        * this is the variable description
        * 
        * @param  string  $varname  desc
        *'.'/',
      'tag' => 'param',
      'out' => array(array(
        'type' => 'string',
        'varname' => '$varname',
        'desc' => 'desc'
      ))
    );
    $test_list['one param no name'] = array(
      'in' => '/'.'**
        * this is the variable description
        * 
        * @param  string  desc
        *'.'/',
      'tag' => 'param',
      'out' => array(array(
        'type' => 'string',
        'varname' => '',
        'desc' => 'desc'
      ))
    );
    $test_list['two full params'] = array(
      'in' => '/'.'**
        * this is the variable description
        * 
        * @param  string  $varname  one desc
        * @param  array $var2 this is another desc        
        *'.'/',
      'tag' => 'param',
      'out' => array(
        array(
          'type' => 'string',
          'varname' => '$varname',
          'desc' => 'one desc'
        ),
        array(
          'type' => 'array',
          'varname' => '$var2',
          'desc' => 'this is another desc'
        )
      )
    );
    $test_list['Simple var no description'] = array(
      'in' => '/'.'**
        * this is the variable description
        * 
        * @var  ClassName
        *'.'/',
      'tag' => 'var',        
      'out' => array(
        'type' => 'ClassName',
        'desc' => ''
      )
    );
    $test_list['Simple var with description'] = array(
      'in' => '/'.'**
        * this is the variable description
        * 
        * @var  ClassName this is the classname description
        *'.'/',
      'tag' => 'var',        
      'out' => array(
        'type' => 'ClassName',
        'desc' => 'this is the classname description'
      )
    );
    
    foreach($test_list as $msg => $test_map){
    
      $rdc = new ReflectionDocBlock($test_map['in']);
      $this->assertEquals($test_map['out'],$rdc->getParsedTag($test_map['tag']),$msg);
    
    }//foreach
  
  }//method

}//class
