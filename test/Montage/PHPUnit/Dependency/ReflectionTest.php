<?php
namespace Montage\Test\PHPUnit {

  use Montage\Dependency\Reflection;
  use PHPUnit\TestCase;
  
  class ReflectionTest extends TestCase {
  
    protected $instance = null;
    
    public function setUp(){
    
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
    
    /**
     *  test to make sure dependencies get set correctly
     *  
     *  @since  8-22-11
     */   
    public function testDependencies(){
    
      $reflection = $this->instance;
      ///\out::e($reflection->getClass('iA'));
      
      $reflection->addFile("C:\Projects\Sandbox\Montage\_active\src\Montage\Request\Request.php");
      \out::e($reflection->getClass('Montage\Request\Request'));
      
      // @todo  I think there is an issue with the reflection where if it loads the child
      // class file before the parent class file then all the dependencies won't exist, this should
      // be tested and fixed
    
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
  
  interface iDependenciesA {}//interface
  interface iDependenciesB {}//interface
  class iA extends b implements iDependenciesA,iDependenciesB {}//class

}//namespace
