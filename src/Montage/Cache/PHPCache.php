<?php

/**
 *  handles caching into native php 
 *  
 *  @note this class cannot cache objects, Montage\Cache\Cache uses serialize, so it
 *  can cache objects 
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 8-2-11
 *  @package montage
 *  @subpackage cache  
 ******************************************************************************/
namespace Montage\Cache;

use Montage\Cache\Cache;
use Montage\Path;

class PHPCache extends Cache {
  
  /**
   *  @see  parent::get()
   *  
   *  overloaded to just include the cache and return the included result      
   */
  public function get($key){
  
    $ret_val = null;
    $path = $this->getPath($key);
    
    if($path->exists()){
    
      $ret_val = include($path);
    
    }//if
    
    return $ret_val;
    
  }//method
  
  /**
   *  @see  parent::getKey()
   */
  protected function getKey($val){ return parent::getKey($val).'.php'; }//method
  
  /**
   *  @see  parent::encodeStr()
   */
  protected function encodeStr($val){
  
    $ret_str = '<'.'?'.'php'.PHP_EOL;
  
    $ret_str .= sprintf('return %s;',$this->encodeVal($val)).PHP_EOL;

    return $ret_str;
  
  }//method
  
  /**
   *  get a native php code value for $val
   *  
   *  @param  mixed $val
   *  @return string
   */
  protected function encodeVal($val){
  
    $ret_str = '';
  
    if(is_string($val)){
      
      $ret_str = sprintf("'%s'",$val);
    
    }else if(is_array($val)){
    
      $ret_str = $this->encodeArr($val);
    
    }else if(is_null($val)){
    
      $ret_str = 'null';
    
    }else if(is_bool($val)){
    
      $ret_str = $val ? 'true' : 'false';
    
    }else if(is_object($val)){
    
      throw new \UnexpectedValueException(
        sprintf('cannot cache objects using %s class',get_class($this))
      );
    
    }else{
    
      $ret_str = (string)$val;
    
    }//if/else if.../else
  
    return $ret_str;
  
  }//method
  
  /**
   *  go through and encode an entire array
   *  
   *  @param  array $arr  the array to turn into a code string      
   *  @return string
   */
  protected function encodeArr(array $arr){
  
    $ret_str = 'array('.PHP_EOL;
    
    foreach($arr as $key => $val){
    
      $ret_str .= sprintf('  %s => %s,',$this->encodeVal($key),$this->encodeVal($val)).PHP_EOL;
    
    }//foreach
    
    $ret_str = rtrim($ret_str,','.PHP_EOL).PHP_EOL;
    $ret_str .= ')';
  
    return $ret_str;
  
  }//method

}//class     
