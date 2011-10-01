<?php

/**
 *  handles caching into native php 
 *  
 *  @note this class cannot cache objects, Montage\Cache\Cache uses serialize, so it
 *  can cache objects 
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 8-2-11
 *  @package montage
 *  @subpackage cache  
 ******************************************************************************/
namespace Montage\Cache;

use Montage\Cache\Cache;
use Path;

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
   *  @param  string  $arr_prefix how much whitespace an array should have   
   *  @return string
   */
  protected function encodeVal($val,$arr_prefix = ''){
  
    $ret_str = '';
  
    if(is_string($val)){
      
      $ret_str = sprintf("'%s'",$val);
    
    }else if(is_array($val)){
    
      $ret_str = $this->encodeArr($val,$arr_prefix);
    
    }else if(is_null($val)){
    
      $ret_str = 'null';
    
    }else if(is_bool($val)){
    
      $ret_str = $val ? 'true' : 'false';
    
    }else if(is_object($val)){
    
      // we are kind of hacky here to let objects be stored in cache, basically, the objects
      // will be serialized into a string that is wrapped in unserialize. I got this from Symfony 2.0
      // Client::getScript() methods
    
      $ret_str = sprintf("unserialize('%s')",serialize($val));
    
    }else{
    
      $ret_str = (string)$val;
    
    }//if/else if.../else
  
    return $ret_str;
  
  }//method
  
  /**
   *  go through and encode an entire array
   *  
   *  @param  array $arr  the array to turn into a code string
   *  @param  string  $prefix how much whitespace each key should have before it, just for readability        
   *  @return string
   */
  protected function encodeArr(array $arr,$prefix = ''){
  
    $ret_str = 'array('.PHP_EOL;
    
    foreach($arr as $key => $val){
    
      $ret_str .= sprintf('%s  %s => %s,',$prefix,$this->encodeVal($key),$this->encodeVal($val,$prefix.'  ')).PHP_EOL;
    
    }//foreach
    
    $ret_str = rtrim($ret_str,','.PHP_EOL).PHP_EOL;
    $ret_str .= $prefix.')';
  
    return $ret_str;
  
  }//method

}//class     
