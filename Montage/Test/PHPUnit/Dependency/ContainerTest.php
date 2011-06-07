<?php
namespace Montage\Test\PHPUnit;

use Montage\Dependency\Reflection;
use Montage\Dependency\Container;
use out;

require_once('out_class.php');

require_once(__DIR__.'/../Test.php');
require_once(__DIR__.'/../../../Path.php');
require_once(__DIR__.'/../../../Field.php');

require_once(__DIR__.'/../../../Dependency/Container.php');
require_once(__DIR__.'/../../../Dependency/Reflection.php');
require_once(__DIR__.'/../../../Dependency/Injector.php');

class ContainerTest extends Test {

  protected $container = null;

  public function setUp(){
  
    $reflection = new Reflection();
    $reflection->addPath(__DIR__.'/../../Fixtures/Dependency');
    
    $this->container = new Container($reflection);
  
  }//method
  
  public function testFindInstanceNoParams(){
  
    $instance = $this->container->findInstance('Montage\Test\Fixtures\Dependency\Foo');
    $this->assertTrue($instance instanceof \Montage\Test\Fixtures\Dependency\Bar);
    $this->assertTrue($instance->che instanceof \Che);
  
  }//method
  
  public function testFindInstanceFieldParams(){
  
    $this->container->setField('one',__FUNCTION__);
    $this->container->setField('two',__METHOD__);
  
    $instance = $this->container->findInstance('Baz');
    $this->assertTrue($instance instanceof \Baz);
    
    $this->assertEquals(__FUNCTION__,$instance->one);
    $this->assertEquals(__METHOD__,$instance->two);
    $this->assertNull($instance->three);
    
  }//method

  /**
   *  tests creating Path objects passing in different random bits   
   */
  public function xtestCreation(){
  
    $base = $this->getFixture();
    
    $test_list = array();
    $test_list[] = array(
      'in' => array($base,'Path'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path'))
    );
    $test_list[] = array(
      'in' => array($base,'Path/'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path'))
    );
    $test_list[] = array(
      'in' => array($base,'\\Path'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path'))
    );
    $test_list[] = array(
      'in' => array($base,'/Path'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path'))
    );
    $test_list[] = array(
      'in' => array($base,array('Path','foo'),'1'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path','foo','1'))
    );
    $test_list[] = array(
      'in' => array(new Path($base),array('Path','foo'),'1'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path','foo','1'))
    );
    $test_list[] = array(
      'in' => array($base,array(array('Path','foo')),' ','','1'),
      'path_str' => join(DIRECTORY_SEPARATOR,array($base,'Path','foo','1'))
    );
    
    foreach($test_list as $test_map){
    
      $rclass = new ReflectionClass('Montage\\Path');
      $instance = $rclass->newInstanceArgs($test_map['in']);
      
      $this->assertEquals($test_map['path_str'],$instance->__toString());
    
    }//foreach
  
  }//method
  

}//class
