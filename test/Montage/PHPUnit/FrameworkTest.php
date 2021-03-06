<?php
/**
 *  this tests the Framework.php file, which is the core of the entire framework
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @package test
 *  @subpackage PHPUnit
 ******************************************************************************/
namespace Montage\PHPUnit;

use PHPUnit\TestCase;
use Montage\Framework;
use Montage\Dependency\Container;

class FrameworkTest extends TestCase {

  protected $framework = null;

  protected function setUp(){
  
    $env = 'test';
    $debug = 1;
    $app_path = '.';
  
    $this->framework = new TestFramework($env,$debug,$app_path);
  
    $container = new TestContainer();
    
    $this->framework->setContainer($container);
  
  }//method

  public function testNormalizeControllerGetParam(){
  
    $func = function($one,$two,array $rest,$post_param){};
    $rfunc = new \ReflectionFunction($func);
    
    // first test without post or catchall params...
    $rfunc_params = array('one','two');
    
    $this->assertException(
      'Exception',
      array($this->framework,'normalizeControllerParams'),
      array($rfunc,$rfunc_params)
    );
    
    ///$normalized_params = $this->framework->normalizeControllerParams($rfunc,$rfunc_params);
  
  
  }//method

   /**
   *  test passing simple controller params
   */
  public function testSimpleNormalizeControllerParams(){
  
    $func = function($foo,$bar){};
    $rfunc = new \ReflectionFunction($func);
    $rfunc_params = array('foo' => 1,'bar' => 2);
    $expected_params = array(1,2);
    
    $normalized_params = $this->framework->normalizeControllerParams($rfunc,$rfunc_params);
    $this->assertEquals($expected_params,$normalized_params);
    
    $rfunc_params = array('foo','bar');
    $expected_params = array('foo','bar');
    
    $normalized_params = $this->framework->normalizeControllerParams($rfunc,$rfunc_params);
    $this->assertEquals($expected_params,$normalized_params);
    
  }//method

  /**
   *  test the array catchall
   *   
   *  @since  7-25-11
   */
  public function testCatchallNormalizeControllerParams(){
  
    // try with just the catch-all...
    $func = function(array $catchall){};
    $rfunc = new \ReflectionFunction($func);
    
    $rfunc_params = array('foo','bar');
    $expected_params = array(array('foo','bar'));
    
    $normalized_params = $this->framework->normalizeControllerParams($rfunc,$rfunc_params);
    $this->assertEquals($expected_params,$normalized_params);
    
    // try with one simple value before the cactch-all...
    $func = function($foo,array $catchall){};
    $rfunc = new \ReflectionFunction($func);
    
    $rfunc_params = array('foo','bar','baz');
    $expected_params = array('foo',array('bar','baz'));
    
    $normalized_params = $this->framework->normalizeControllerParams($rfunc,$rfunc_params);
    $this->assertEquals($expected_params,$normalized_params);
    
    // now try with a request value...
    $func = function($foo,array $catchall = array('che' => 0)){};
    $rfunc = new \ReflectionFunction($func);
    $_POST['che'] = 1;
    
    $rfunc_params = array('foo');
    $expected_params = array('foo',array('che' => 1));
    
    $normalized_params = $this->framework->normalizeControllerParams($rfunc,$rfunc_params);
    $this->assertEquals($expected_params,$normalized_params);
    
    // now try with a request value set and one default...
    $func = function($foo,array $catchall = array('che' => 0,'bar' => 1)){};
    $rfunc = new \ReflectionFunction($func);
    $_POST['che'] = 1;
    
    $rfunc_params = array('foo');
    $expected_params = array('foo',array('che' => 1,'bar' => 1));
    
    $normalized_params = $this->framework->normalizeControllerParams($rfunc,$rfunc_params);
    $this->assertEquals($expected_params,$normalized_params);
    
    // bug fix...
    $func = function(array $catchall = array('rurl' => '')){};
    $rfunc = new \ReflectionFunction($func);
    
    $rfunc_params = array();
    $expected_params = array(array('rurl' => ''));
    
    $normalized_params = $this->framework->normalizeControllerParams($rfunc,$rfunc_params);
    $this->assertEquals($expected_params,$normalized_params);
    
  }//method
  
}//class

/**
 *  makes protected methods public for testing purposes
 *  
 *  if I had php >= 5.3.2 
 *  $rurl = new \ReflectionObject($instance); 
 *  $rmethod = $rurl->getMethod('parse'); 
 *  $rmethod->setAccessible(true);
 *  
 *  @link http://stackoverflow.com/questions/249664/best-practices-to-test-protected-methods-with-phpunit  
 */
class TestFramework extends Framework {

  public function normalizeControllerParams(\ReflectionFunctionAbstract $rfunc,array $params){
    return parent::normalizeControllerParams($rfunc,$params);
  }//method

}//method

class TestContainer extends Container {

  /**
   *  find the class name that will be used to create the instance
   *
   *  @since  8-3-11   
   *  @param  string  $class_name the key/name of the class you are looking for
   *  @return string  the class name that will be used to create the instance      
   */
  public function getClassName($class_name){ return ''; }//method

  /**
   *  get an instance
   *
   *  @param  string  $class_name the name of the class you are looking for
   *  @param  array $params any params you want to pass into the constructor of the instance      
   */
  public function getInstance($class_name,$params = array()){
  
    $ret_instance = null;
  
    if($class_name === 'Montage\Request\Requestable'){
    
      $ret_instance = new \Montage\Request\Request($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
    
    }//if
  
    return $ret_instance;
    
  }//method

}//class
