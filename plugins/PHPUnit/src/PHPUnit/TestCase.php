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

abstract class TestCase extends PHPUnit_Framework_TestCase {
  
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
