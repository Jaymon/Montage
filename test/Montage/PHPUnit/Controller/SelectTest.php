<?php

namespace Montage\PHPUnit {

  require_once(__DIR__.'/../Test.php');

  use Montage\Reflection\ReflectionFramework;
  use Montage\Controller\Select;
  
  class SelectTest extends Test {
  
    protected $cselect = null;
  
    public function setUp(){
    
      $reflection = new ReflectionFramework();
      
      $reflection->addFile(__DIR__.'../../../../../src/Montage/Controller/Controllable.php');
      $reflection->addFile(__DIR__.'../../../../../src/Montage/Controller/Controller.php');
      $reflection->addFile(__FILE__);
      
      $this->cselect = new Select($reflection);
      
    }//method
    
    public function testFind(){
    
      $test_list = array();
      $test_list[] = array(
        'in' => array('','bar/baz',array()),
        'out' => array('Controller\IndexController','handleIndex',array('bar','baz'))
      );
      $test_list[] = array(
        'in' => array('','foo',array()),
        'out' => array('Test\Controller\FooController','handleIndex',array())
      );
      
      $method = 'find';
      $this->assertCalls($this->cselect,$method,$test_list);
  
    }//method
  
  }//class

}//namespace

namespace Test\Controller {

  use Montage\Controller\Controller;
  
  class FooController extends Controller {
  
    public function handleIndex(array $params = array()){}//method
  
  }//class

}//namespace

namespace Controller {

  use Montage\Controller\Controller;
  
  class IndexController extends Controller {
  
    public function handleIndex(array $params = array()){}//method
  
  }//class

}//namespace
