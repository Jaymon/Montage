<?php
namespace Montage\PHPUnit;

use PHPUnit_Framework_TestCase;
use ReflectionClass;

use Montage\AutoLoad\FrameworkAutoloader;

error_reporting(-1);
ini_set('display_errors','on');

require_once('out_class.php');

require_once(__DIR__.'/../../../src/Montage/AutoLoad/AutoLoadable.php');
require_once(__DIR__.'/../../../src/Montage/AutoLoad/AutoLoader.php');
require_once(__DIR__.'/../../../src/Montage/AutoLoad/FrameworkAutoloader.php');

// add the framework autoloader...
$fal = new FrameworkAutoloader('Montage',realpath(__DIR__.'/../../../src'));
$fal->register();

// add a test autoloader...
$tal = new FrameworkAutoloader('Montage\PHPUnit',realpath(__DIR__.'/../..'));
$tal->register();

$sal = new FrameworkAutoloader('Symfony',realpath(__DIR__.'/../../../plugins/Symfony/vendor'));
$sal->register();

abstract class Test extends PHPUnit_Framework_TestCase {

  /**
   *  returns the absolute base of where all the fixtures live
   *   
   *  @param  string  $args,... one or more bits of a path   
   *  @return string
   */
  protected function getFixture(){
  
    $path_bits = func_get_args();
    return join(DIRECTORY_SEPARATOR,array_merge(array($this->getTestBase(),'Fixtures'),$path_bits));
  
  }//method
  
  /**
   *  returns the base of where this test's fixtures live
   *   
   *  @return string
   */
  /* protected function getClassFixtureBase(){
  
    \out::e(get_class($this));
  
    $rclass = new ReflectionClass(get_class($this));
    $filename = $rclass->getFileName();
    $dir = dirname($filename);
    $base = dirname(__FILE__);
    $diff = str_replace($base,'',$dir);
    $ret_str = sprintf('%s%s',$this->getFixtureBase(),$diff);
    \out::e($ret_str);
    return $ret_str;
  
  }//method */
  
  /**
   *  returns the test base directory
   *   
   *  @return string
   */
  protected function getTestBase(){
    return realpath(join(DIRECTORY_SEPARATOR,array(__DIR__,'..')));
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
  
    foreach($test_list as $i => $test_map){
    
      try{
      
        if(!is_array($test_map['in'])){ $test_map['in'] = (array)$test_map['in']; }//if
      
        $ret = call_user_func_array(array($instance,$method),$test_map['in']);
        $this->assertEquals($test_map['out'],$ret,sprintf('Iteration %s',$i));
        
      }catch(\Exception $e){
      
        if(!is_string($test_map['out']) || !($e instanceof $test_map['out'])){
          throw $e;
        }//if
      
      }//try/catch
    
    }//foreach
    
  
  
  }//method

}//class
