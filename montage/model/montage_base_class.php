<?php

/**
 *  base class for lots of montage main objects
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-19-10
 *  @package montage 
 ******************************************************************************/
abstract class montage_base {

  /**
   *  holds the key/value mapping for different tags of the feed
   *  
   *  @var  array
   */
  protected $field_map = array();


  /**
   *  set the $val into $key
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return mixed return $val
   */
  function setField($key,$val){
    $this->field_map[$key] = $val;
    return $this->field_map[$key];
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  function hasField($key){ return !empty($this->field_map[$key]); }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  function existsField($key){ return array_key_exists($key,$this->field_map); }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  function getField($key,$default_val = null){
    return $this->existsField($key) ? $this->field_map[$key] : $default_val;
  }//method
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return mixed the value of key before it was removed
   */
  function killField($key){
    $ret_val = null;
    if($this->hasField($key)){
      $ret_val = $this->field_map[$key];
      unset($this->field_map[$key]);
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
  function isField($key,$val){
    $ret_bool = false;
    if($this->existsField($name)){
      $ret_bool = $this->getField($name) == $val;
    }//if
    return $ret_bool;
  }//method
  
  /**
   *  add all the fields in $field_map to the instance field_map
   *  
   *  the past in $field_map takes precedence, it will overwrite previous values
   *      
   *  @param  array $field_map      
   *  @return array
   */
  function setFields($field_map){
  
    if(!empty($field_map)){
      $this->field_map = array_merge($this->field_map,$field_map);
    }//if
    return $this->field_map;
  
  }//method
  
  /**
   *  return the instance's field_map
   *  
   *  @return array
   */
  function getFields(){ return $this->field_map; }//method

}//class     
