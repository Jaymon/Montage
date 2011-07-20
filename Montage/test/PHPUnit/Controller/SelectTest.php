<?php

namespace Montage\Test\PHPUnit {

  require_once('out_class.php');
  
  require_once(__DIR__.'/Test.php');
  require_once(__DIR__.'/../../Field.php');
  require_once(__DIR__.'/../../Controller/Controllable.php');
  require_once(__DIR__.'/../../Controller/Controller.php');
  require_once(__DIR__.'/../../Controller/Forward.php');
  require_once(__DIR__.'/../../Path.php');
  
  require_once(__DIR__.'/../../Dependency/Container.php');
  require_once(__DIR__.'/../../Dependency/Reflection.php');
  require_once(__DIR__.'/../../Dependency/Injector.php');

  use Montage\Dependency\Reflection;
  use Montage\Dependency\Container;
  use out;
  
  class SelectTest extends Test {
  
    protected $container = null;
  
    public function setUp(){
    
      ///out::e($this->container);
    
      $reflection = new Reflection();
      ///$reflection->addPath(__DIR__.'/../Fixtures/Forward/Controller');
      $reflection->addPath(__DIR__.'/../../Controller');
      
      $reflection->addClass('Montage\Dependency\Reflection');
      $reflection->addClass('Controller\IndexController');
      
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
  
    public function testFind(){
    
      $test_list = array();
      $test_list[] = array(
        'in' => array('','bar/baz',array()),
        'out' => array('Controller\IndexController','handleIndex',array('bar','baz'))
      );
      
      foreach($test_list as $test_map){
      
        $instance = $this->container->getInstance('Montage\Forward');
        $ret = call_user_func_array(array($instance,'find'),$test_map['in']);
        $this->assertSame($test_map['out'],$ret);
        
      }//foreach
  
    }//method
  
  }//class

}//namespace

namespace Test\Controller {

  use Montage\Controller\Controller;
  
  class IndexController extends Controller {
  
    public function handleIndex(){}//method
  
  }//class

}//namespace
