<?php
namespace Montage\Test\PHPUnit {

  use Montage\Dependency\Reflection;
  use out;
  
  require_once('out_class.php');
    
  require_once(__DIR__.'/../Test.php');
  require_once(__DIR__.'/../../../Path.php');
  
  require_once(__DIR__.'/../../../Cache/Cacheable.php');
  require_once(__DIR__.'/../../../Cache/ObjectCache.php');
  require_once(__DIR__.'/../../../Dependency/Reflection.php');
  require_once(__DIR__.'/../../../Dependency/ReflectionFile.php');
  
  class ReflectionTest extends Test {
  
    protected $instance = null;
    
    public function setUp(){
    
      ///out::e($this->container);
    
      $this->instance = new Reflection();
      $this->instance->addFile(__FILE__);
      
    }//method

    /**
     *  @since  6-20-11
     */
    public function testFindClassNames(){
    
      $test_list = array();
      $test_list[] = array(
        'in' => array('a',array()),
        'out' => array(
          '\aa',
          '\ac'
        )
      );
      
      $test_list[] = array(
        'in' => array('a',array('ac')),
        'out' => array(
          '\aa',
        )
      );
    
      $this->assertCalls($this->instance,'findClassNames',$test_list);
    
    }//method
  
    public function testFindClassName(){

      $test_list = array();
      $test_list[] = array(
        'in' => array('b'),
        'out' => '\cc'
      );
      $test_list[] = array(
        'in' => array('a'),
        'out' => '\LogicException'
      );
    
      $this->assertCalls($this->instance,'findClassName',$test_list);
    
    }//method
  
  }//class

}//namespace

namespace {

  class a {}//class
  class aa extends a {}//class
  class ab extends a {}//class
  class ac extends ab {}//class
  
  class b {}//class
  class bb extends b {}//class
  class cc extends bb {}//class

}//namespace
