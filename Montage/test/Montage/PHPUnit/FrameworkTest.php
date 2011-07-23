<?php
namespace Montage\PHPUnit;

require_once(__DIR__.'/Test.php');

use Montage\Framework;
use Montage\Dependency\Container;

class FrameworkTest extends Test {

  protected $framework = null;

  protected function setUp(){
  
    $env = 'test';
    $debug = 1;
    $app_path = '.';
  
    $this->framework = new TestFramework($env,$debug,$app_path);
  
    $container = new TestContainer();
    
    $this->framework->setContainer($container);
  
  }//method

   /**
   *  test the build method
   */
  public function testNormalizeControllerParams1(){
  
    $rmethod = new \ReflectionMethod($this,'ControllerNCP1');
    $rmethod_params = array('foo' => 1,'bar' => 2);
    
    $normalized_params = $this->framework->normalizeControllerParams($rmethod,$rmethod_params);
    \out::e($normalized_params);
  
  }//method

  public function ControllerNCP1($foo,$bar){}//method

}//class

/**
 *  makes protected methods public for testing purposes
 *  
 *  if I had php >= 5.3.2 
 *  $rurl = new \ReflectionObject($this->url); 
 *  $rmethod = $rurl->getMethod('parse'); 
 *  $rmethod->setAccessible(true);
 *  
 *  @link http://stackoverflow.com/questions/249664/best-practices-to-test-protected-methods-with-phpunit  
 */
class TestFramework extends Framework {

  public function normalizeControllerParams(\ReflectionMethod $rmethod,array $params){
    return parent::normalizeControllerParams($rmethod,$params);
  }//method

}//method

class TestContainer extends Container {

  /**
   *  when you know what class you specifically want, use this method over {@link findInstance()}
   *
   *  @param  string  $class_name the name of the class you are looking for
   *  @param  array $params any params you want to pass into the constructor of the instance      
   */
  public function getInstance($class_name,$params = array()){ return null; }//method

}//class
