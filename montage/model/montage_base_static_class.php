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
   *  keep a singleton montage_base instance that will map the static methods to
   *  the equivalent montage_base method so we only have to keep one master method
   *  instead of a static version of the montage_base::*field methods      
   *
   *  @var  montage_base
   */
  protected static $field_instance = null;

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
    $field_map = self::getFieldInstance();
    return $field_map->setField($key,$val);
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  static public function hasField($key){
    $field_map = self::getFieldInstance();
    return $field_map->hasField($key);
  }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  static public function existsField($key){
    $field_map = self::getFieldInstance();
    return $field_map->existsField($key);
  }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  static public function getField($key,$default_val = null){
    $field_map = self::getFieldInstance();
    return $field_map->getField($key,$default_val);
  }//method
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return mixed the value of key before it was removed
   */
  static public function killField($key){
    $field_map = self::getFieldInstance();
    return $field_map->killField($key);
  }//method
  
  /**
   *  check's if a field exists and is equal to $val
   *  
   *  @param  string  $key  the name
   *  @param  string  $val  the value to compare to the $key's set value
   *  @return boolean
   */
  static public function isField($key,$val){
    $field_map = self::getFieldInstance();
    return $field_map->isField($key,$val);
  }//method
  
  /**
   *  bump the field at $key by $count
   *  
   *  @since  5-26-10
   *      
   *  @param  string  $key  the name
   *  @param  integer $count  the value to increment $key
   *  @return integer the incremented value now stored at $key
   */
  static public function bumpField($key,$count){
    $field_map = self::getFieldInstance();
    return $field_map->bumpField($key,$count);
  }//method
  
  /**
   *  get the field instance this class uses
   *  
   *  @return montage_base
   */
  protected static function getFieldInstance(){
    if(self::$field_instance === null){
      self::$field_instance = new montage_base();
    }//if
    return self::$field_instance;
  }//method

}//class     
