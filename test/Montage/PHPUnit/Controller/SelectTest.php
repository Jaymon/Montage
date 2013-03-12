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

  ///require_once('/vagrant/public/out_class.php');

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
      $this->cselect->reflection->addFile(__FILE__);
      
    }//method

    public function testNormalizeName(){

      $rmethod = new \ReflectionMethod($this->cselect, 'normalizeClassName');
      $rmethod->setAccessible(true);

      $tests = array(
        array(
          'in' => array('', 'Foo', 'Endpoint'),
          'out' => '\\FooEndpoint'
        ),
        array(
          'in' => array('', 'fOo', 'Command'),
          'out' => '\\FooCommand'
        ),
        array(
          'in' => array('\\Foo\\Bar', 'Foo', 'Command'),
          'out' => '\\Foo\\Bar\\FooCommand'
        ),
      );

      foreach($tests as $test){
        $ret = $rmethod->invokeArgs($this->cselect, $test['in']);
        $this->assertEquals($ret, $test['out']);
      }//foreach
    }//method

    public function xtestFind(){

      $test_list = array();
      $test_list[] = array(
        'in' => array('POST', 'example.com', 'foo/', array()),
        'out' => array(
          'Test\\Controller\\FooEndpoint',
          array('handlePostDefault', 'handleDefault'),
          array()
        )
      );
      $test_list[] = array(
        'in' => array('POST', 'example.com', 'foo/bar', array()),
        'out' => array(
          'Test\\Controller\\FooEndpoint',
          array('handlePostBar'),
          array()
        )
      );
      $test_list[] = array(
        'in' => array('GET', 'example.com', 'foo/baz', array()),
        'out' => array(
          'Test\\Controller\\FooEndpoint',
          array('handleBaz'),
          array()
        )
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

  use Montage\Controller\Endpoint;
  use Montage\Controller\Command;

  class FooEndpoint extends Endpoint {

    public function handlePostBar(array $params = array()){}//method
    public function handleBaz(array $params = array()){}//method
  
    public function handlePostDefault(array $params = array()){}//method
    public function handleDefault(array $params = array()){}//method
    public function errorPostDefault(\Exception $e){}//method
    public function errorDefault(\Exception $e){}//method
  
  }//class
  
  class BazCommand extends Command {
  
    public function handleIndex(array $params = array()){}//method
  
  }//class

}//namespace
