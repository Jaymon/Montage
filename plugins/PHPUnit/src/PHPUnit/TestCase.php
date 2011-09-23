<?php
/**
 *  base testing class
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 7-28-11
 *  @package test
 *  @subpackage PHPUnit
 ******************************************************************************/
namespace PHPUnit;

use PHPUnit_Framework_TestCase;
use Montage\Path;

abstract class TestCase extends PHPUnit_Framework_TestCase {
  
  /**
   *  holds the fixture directory name
   *
   *  @since  8-30-11
   *  @var  string      
   */
  protected $fixture_dir = 'fixtures';
  
  /**
   *  returns the closest fixture path to this class
   *
   *  @since  8-30-11
   *  @param  string  $path,... one or more path bits to append to the found path
   *  @return string  the full path      
   */
  protected function getFixturePath($path = ''){
  
    $ret_str = '';
    $path_bits = func_get_args();
  
    // get the directory the class is located in...
    $rclass = new \ReflectionClass($this);
    $class_filename = $rclass->getFileName();
    $class_path = dirname($class_filename);
    
    // now move backward until you find the "test directory"
    $path = new Path($class_path);
    if($test_path = $path->getParent('#test#')){
    
      $ret_path = new Path($test_path,$this->fixture_dir,$path_bits);
    
    }else{
    
      throw new \UnexpectedValueException(
        sprintf('The class "%s" does not reside in a test directory',$class_filename)
      );
    
    }//if
    
    return (string)$ret_path;
  
  }//method
  
  /**
   *  get a path that is relative to the system's temp dir      
   *
   *  @since  9-21-11
   *  @param  string  $path,... one or more path bits to append to the found path
   *  @return string  the full path   
   */        
  protected function getTempPath($path = ''){
  
    $path_bits = func_get_args();
  
    // get the directory the class is located in...
    $rclass = new \ReflectionClass($this);
    $class_name = $rclass->getShortName();
  
    $ret_path = new Path(sys_get_temp_dir(),$class_name,$path_bits);
    return (string)$ret_path;
  
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
    
      if(!is_string($test_map['out']) || !($e instanceof $test_map['out'])){
        throw $e;
      }//if
    
    }//try/catch
  
  }//method

}//class
