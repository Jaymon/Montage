<?php

/**
 *  handy functions that don't seem to go anywhere else. A toolbox of sorts
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 3-26-10
 *  @package montage
 *  @subpackage help 
 ******************************************************************************/
class montage_util {

  /**
   *  generate a backtrace list
   *  
   *  @param  integer $skip how many rows to skip of debug_backtrace   
   *  @return array a list of method calls from first to latest (by default debug_backtrace gives latest to first)
   */
  static function getBacktrace($skip = 1){
  
    // set the backtrace...
    $backtrace = debug_backtrace();
    $backtrace = array_reverse(array_slice($backtrace,$skip));
    $ret_list = array();
    
    foreach($backtrace as $key => $bt_map){
    
      $class = empty($bt_map['class']) ? '' : $bt_map['class'];
      $method = empty($bt_map['function']) ? '' : $bt_map['function'];
      $file = empty($bt_map['file']) ? 'unknown' : $bt_map['file'];
      $line = empty($bt_map['line']) ? 'unknown' : $bt_map['line'];
      
      $method_call = '';
      if(empty($class)){
        if(!empty($method)){ $method_call = $method; }//if
      }else{
        if(empty($method)){
          $method_call = $class;
        }else{
        
          $method_reflect = new ReflectionMethod($class,$method);
          $method_call = sprintf(
            '%s %s::%s',
            join(' ',Reflection::getModifierNames($method_reflect->getModifiers())),
            $class,
            $method
          );
          
        }//if/else
      }//if/else
      
      $arg_list = array();
      if(!empty($bt_map['args'])){
      
        foreach($bt_map['args'] as $arg){
        
          if(is_object($arg)){
            $arg_list[] = get_class($arg);
          }else{
            if(is_array($arg)){
              $arg_list[] = sprintf('Array(%s)',count($arg));
            }else if(is_bool($arg)){
              $arg_list[] = $arg ? 'TRUE' : 'FALSE';
            }else if(is_null($arg)){
              $arg_list[] = 'NULL';
            }else if(is_string($arg)){
              $arg_list[] = sprintf('"%s"',$arg);
            }else{
              $arg_list[] = $arg;
            }//if/else
          }//if/else
        
        }//foreach
      
      }//if
      
      $ret_list[] = sprintf('%s(%s) - %s:%s',$method_call,join(', ',$arg_list),$file,$line);
      
    }//foreach
  
    return $ret_list;
  
  }//method
  
  /**
   *  function to make passing arguments into a CLI script easier
   *  
   *  an argument has to be in the form: --name=val or --name if you want name to be true
   *  
   *  if you want to do an array, then specify the name multiple times: --name=val1 --name=val2 will
   *  result in ['name'] => array(val1,val2)
   *  
   *  @param  array $argv the values passed into php from the commmand line 
   *  @return array the key/val mappings that were parsed from --name=val command line arguments
   */
  static function parseArgv($argv)
  {
    // canary...
    if(empty($argv)){ return array(); }//if
  
    $ret_map = array();
  
    foreach($argv as $arg){
    
      // canary...
      if((!isset($arg[0]) || !isset($arg[1])) || ($arg[0] != '-') || ($arg[1] != '-')){
        throw new InvalidArgumentException(
          sprintf('%s does not conform to the --name=value convention',$arg)
        );
      }//if
    
      $arg_bits = explode('=',$arg,2);
      // strip off the dashes...
      $name = mb_substr($arg_bits[0],2);
      $val = isset($arg_bits[1]) ? $arg_bits[1] : true;
      
      if(isset($ret_map[$name])){
      
        if(!is_array($ret_map[$name])){
          $ret_map[$name] = array($ret_map[$name]);
        }//if
        
        $ret_map[$name][] = $val;
        
      }else{
      
        $ret_map[$name] = $val;
        
      }//if/else
    
    }//foreach
  
    return $ret_map;
  
  }//method

}//class     
