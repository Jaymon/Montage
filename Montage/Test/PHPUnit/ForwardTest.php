<?php
namespace Montage\Test\PHPUnit;

use Montage\Dependency\Reflection;
use Montage\Dependency\Container;
use out;

require_once('out_class.php');

require_once(__DIR__.'/Test.php');
require_once(__DIR__.'/../../Forward.php');
require_once(__DIR__.'/../../Field.php');
require_once(__DIR__.'/../../Path.php');

require_once(__DIR__.'/../../Dependency/Container.php');
require_once(__DIR__.'/../../Dependency/Reflection.php');
require_once(__DIR__.'/../../Dependency/Injector.php');

class ForwardTest extends Test {

  protected $container = null;

  public function setUp(){
  
    ///out::e($this->container);
  
    $reflection = new Reflection();
    $reflection->addPath(__DIR__.'/../Fixtures/Forward/Controller');
    $reflection->addPath(__DIR__.'/../../Controller');
    
    $reflection->addClass('Montage\Dependency\Reflection');
    
    $this->container = new Container($reflection);
    
    ///out::e(spl_autoload_functions());
  
  }//method
  
  public function tearDown(){
  
    if(!empty($this->container)){
    
      // we need to unregister the autoloader...
      $reflection = $this->container->getReflection();
      $reflection->__destruct();
      
    }//if
  
  }//method

  public function testFindCLI(){
  
    $test_list = array();
    $test_list[] = array(
      'in' => array('foo.bar/baz',array()),
      'out' => array()
    );
    
    foreach($test_list as $test_map){
    
      $instance = $this->container->getInstance('Montage\Forward');
      $ret = call_user_func_array(array($instance,'findCLI'),$test_map['in']);
      
      out::e($ret);
      
    }//foreach

  }//method

}//class
