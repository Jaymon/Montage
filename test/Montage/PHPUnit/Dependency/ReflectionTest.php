<?php
namespace {

  include_once('out_class.php');

  use Montage\Dependency\Reflection;
  
  class ReflectionTest extends \PHPUnit_Framework_TestCase {
  
    protected static $base_path = '';
    protected static $base_src_path = '';
    protected static $base_fixture_path = '';
    
    protected $instance = null;
    
    /**
     *  this will be called before the class is initalized, so override if you want
     *  to do global test specific configuration
     *  
     *  @link http://www.phpunit.de/manual/current/en/fixtures.html#fixtures.more-setup-than-teardown
     */
    public static function setUpBeforeClass(){
    
      self::$base_path = realpath(__DIR__.'/../../../..');
      self::$base_src_path = realpath(self::$base_path.'/src/Montage');
      self::$base_fixture_path = realpath(self::$base_path.'/test/fixtures');
    
      require_once(self::$base_path.'/Plugins/Utilities/src/Path.php');
      require_once(self::$base_src_path.'/Cache/Cacheable.php');
      require_once(self::$base_src_path.'/Cache/ObjectCache.php');
      
      require_once(self::$base_src_path.'/Dependency/ReflectionFile.php');
      require_once(self::$base_src_path.'/Dependency/Reflection.php');
    
    }//method
    
    public function setUp(){
    
      $this->instance = new Reflection();
      $this->instance->addFile(__FILE__);
      
      
      
    }//method

    /**
     *  @since  9-12-11
     */   
    public function testFindShortNames(){
    
      $test_list = array();
      $test_list[] = array(
        'in' => array('z'),
        'out' => array(
          'Foo\Bar\xz',
          'Foo\Bar\yz'
        )
      );
      $test_list[] = array(
        'in' => array('wz'),
        'out' => array(
          'Foo\Bar\wz'
        )
      );
      
      $this->assertCalls($this->instance,'findShortNames',$test_list);
    
    }//method

    /**
     *  @since  6-20-11
     */
    public function testFindClassNames(){
    
      $test_list = array();
      $test_list[] = array(
        'in' => array('a',array()),
        'out' => array(
          'aa',
          'ac'
        )
      );
      
      $test_list[] = array(
        'in' => array('a',array('ac')),
        'out' => array(
          'aa',
        )
      );
    
      $this->assertCalls($this->instance,'findClassNames',$test_list);
    
    }//method
  
    public function testFindClassName(){

      $test_list = array();
      $test_list[] = array(
        'in' => array('b'),
        'out' => 'cc'
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
      
      $reflection->addFile(self::$base_fixture_path.'/Dependency/Hija.php');
      $reflection->addFile(self::$base_fixture_path.'/Dependency/Abuelo.php');
      $reflection->addFile(self::$base_fixture_path.'/Dependency/Padre.php');
      
      ///\out::i($reflection);
      
      $class_list = $reflection->getDependencies('\Hija');
      \out::e($class_list);
      $this->assertEquals(2,count($class_list),'Hija class should have 2 dependencies');
      
      
      
      ///$reflection->addFile("C:\Projects\Sandbox\Montage\_active\src\Montage\Request\Request.php");
      ///\out::e($reflection->getClassInfo('Montage\Request\Request'));
      
      // @todo  I think there is an issue with the reflection where if it loads the child
      // class file before the parent class file then all the dependencies won't exist, this should
      // be tested and fixed
    
    }//method
    
    /**
     *  I noticed that I was spending a lot of time setting tests like this up, so I thought
     *  I would abstract it away
     *  
     *  @since  6-24-11
     *  @param  object  $instance the object that will call the method
     *  @param  string  $method the method name
     *  @param  array $test_list  a list of maps with in and out keys set
     */
    protected function assertCalls($instance,$method,array $test_list){
    
      $ret_int = 0;
    
      foreach($test_list as $i => $test_map){
      
        $msg = sprintf('Iteration %s',$i);
        $this->assertCall($instance,$method,$test_map,$msg);
        
        $ret_int++;
      
      }//foreach
    
      return $ret_int;
    
    }//method
    
    /**
     *  call the $instance->$method with $test_map['in'] and compare it to $test_map['out']
     *  
     *  @since  8-1-11
     *  @param  object  $instance the object that will call the method
     *  @param  string  $method the method name
     *  @param  array $test_map an array with atleast in and out keys set
     *  @param  string  $msg  the message that will be printed out if the test fails   
     */
    protected function assertCall($instance,$method,array $test_map,$msg = ''){
    
      try{
        
        if(!is_array($test_map['in'])){ $test_map['in'] = (array)$test_map['in']; }//if
      
        $ret = call_user_func_array(array($instance,$method),$test_map['in']);
        $this->assertEquals($test_map['out'],$ret,$msg);
        
      }catch(\Exception $e){

        if(!($e instanceof \PHPUnit_Framework_ExpectationFailedException)){
  
          if(is_string($test_map['out'])){
  
            $this->assertInstanceOf($test_map['out'],$e);
          
          }else{
          
            throw $e;
          
          }//if/else
          
        }else{
        
          throw $e;
        
        }//if/else
      
      }//try/catch
    
    }//method
  
  }//class

}//namespace

namespace Foo\Bar {

  class z {}//class
  class xz extends z {}//class
  class yz extends z {}//class
  
  class wz {}//class

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
  class iA implements iDependenciesA,iDependenciesB {}//class

}//namespace
