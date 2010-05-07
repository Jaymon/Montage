<?php

/**
 *  base class for montage's main static objects
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-20-10
 *  @package montage 
 ******************************************************************************/
abstract class montage_base_static {

  /**
   *  holds the key/value mapping for different tags of the feed
   *  
   *  @var  array
   */
  protected static $field_map = array();

  /**
   *  here to throw an error if someone tries to instantiate a class that is marked
   *  to be static
   */
  final public function __construct(){
    
    $class_name = get_class($this);
    
    $bt = debug_backtrace();
    $file = empty($bt[0]['file']) ? 'unknown' : $bt[0]['file'];
    $line = empty($bt[0]['line']) ? 'unknown' : $bt[0]['line'];
  
    throw new RuntimeException(
      sprintf(
        'You are trying to instantiate a static object (%s) at %s:%s. This type of '
        .'object should be called statically like %s::method() and should never be '
        .'instantiated. Please see http://php.net/manual/en/language.oop5.static.php for '
        .'more information on static vs. instantiated objects.',
        $class_name,
        $file,
        $line,
        $class_name
      ) 
    );  
  
  }//method

  /**
   *  set the $val into $key
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return mixed return $val
   */
  static public function setField($key,$val){
    self::$field_map[$key] = $val;
    return self::$field_map[$key];
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  static public function hasField($key){ return !empty(self::$field_map[$key]); }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  static public function existsField($key){ return array_key_exists($key,self::$field_map); }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  static public function getField($key,$default_val = null){
    return self::existsField($key) ? self::$field_map[$key] : $default_val;
  }//method
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return mixed the value of key before it was removed
   */
  static public function killField($key){
    $ret_val = null;
    if(self::hasField($key)){
      $ret_val = self::$field_map[$key];
      unset(self::$field_map[$key]);
    }//if
    return $ret_val;
  }//method
  
  /**
   *  check's if a field exists and is equal to $val
   *  
   *  @param  string  $key  the name
   *  @param  string  $val  the value to compare to the $key's set value
   *  @return boolean
   */
  static public function isField($key,$val){
    $ret_bool = false;
    if(self::existsField($name)){
      $ret_bool = self::getField($name) == $val;
    }//if
    return $ret_bool;
  }//method

}//class     
