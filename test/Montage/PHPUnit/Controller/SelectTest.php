<?php
/**
 *  test the class that chooses which controller to run from the requested user input  
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @package test
 *  @subpackage PHPUnit
 ******************************************************************************/
namespace Montage\PHPUnit {

  use PHPUnit_Framework_TestCase;
  use Montage\Reflection\ReflectionFramework;
  use Montage\Controller\Select;
  use Montage\Autoload\FrameworkAutoloader;

  require_once('/vagrant/public/out_class.php');

  $base = realpath(__DIR__.'/../../../..');
  $base_src = $base.'/src';

  // include the needed classes
  require_once($base.'/plugins/Utilities/src/Path.php');
  
  // load the Framework autoloader, this will handle all other dependencies to load this class
  // so I don't have to have a ton of other includes() right here...
  require_once($base_src.'/Montage/Autoload/Autoloadable.php');
  require_once($base_src.'/Montage/Autoload/Autoloader.php');
  require_once($base_src.'/Montage/Autoload/FrameworkAutoloader.php');
  
  $fal = new FrameworkAutoloader('Montage',$base_src);
  $fal->register();
  
  class SelectTest extends PHPUnit_Framework_TestCase {
  
    protected $cselect = null;
  
    public function setUp(){
    
      global $base_src;
    
      $this->cselect = new Select();
      $this->cselect->reflection = new ReflectionFramework();
      
      $this->cselect->reflection->addFile($base_src.'/Montage/Controller/Controllable.php');
      $this->cselect->reflection->addFile($base_src.'/Montage/Controller/Controller.php');
      $this->cselect->reflection->addFile($base_src.'/Montage/Controller/IndexController.php');
      $this->cselect->reflection->addFile($base_src.'/Montage/Controller/ExceptionController.php');
      $this->cselect->reflection->addFile(__FILE__);
      
    }//method
    
    public function testFind(){
    
      $test_list = array();
      $test_list[] = array(
        'in' => array('','bar/baz',array()),
        'out' => array('Test\\Controller\\IndexController','handleIndex',array('bar','baz'))
      );
      $test_list[] = array(
        'in' => array('','foo',array()),
        'out' => array('Test\\Controller2\\FooController','handleIndex',array())
      );
      $test_list[] = array(
        'in' => array('','foo/bar',array()),
        'out' => array('Test\\Controller2\\FooController','handleBar',array())
      );
      
      $method = 'find';
      $this->assertCalls($this->cselect,$method,$test_list);
  
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
        $this->assertEquals($test_map['out'],$ret,(string)$msg);
        
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

namespace Test\Controller {

  use Montage\Controller\Controller;
  
  class FooController extends Controller {
  
    public function handleIndex(array $params = array()){}//method
  
  }//class
  
  class CheController extends Controller {
  
    public function handleIndex(array $params = array()){}//method
  
  }//class
  
  class IndexController extends \Montage\Controller\IndexController {
  
    public function handleIndex(array $params = array()){}//method
  
  }//class

}//namespace

namespace Test\Controller2 {

  class FooController extends \Test\Controller\FooController {
  
    public function handleIndex(array $params = array()){}//method
    
    public function handleBar(array $params = array()){}//method
  
  }//class

}//namespace
