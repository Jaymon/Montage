<?php

/**
 *  The field is just an easy way to store key/value pairs
 *  
 *  the reason this interface exists is to make sure any class can mimick the field
 *  behavior even if it can't extend the Field class 
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-19-10
 *  @package montage 
 ******************************************************************************/
namespace Montage\Field;

interface Fieldable {

  /**
   *  set the $val into $key
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return object  the class instance for fluid interface
   */
  public function setField($key,$val);
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function hasField($key);
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function existsField($key);
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  public function getField($key,$default_val = null);
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return object  the class instance for fluid interface
   */
  public function killField($key);
  
  /**
   *  check's if a field exists and is equal to $val
   *  
   *  @param  string  $key  the name
   *  @param  string  $val  the value to compare to the $key's set value
   *  @return boolean
   */
  public function isField($key,$val);
  
  /**
   *  add all the fields in $field_map to the instance field_map
   *  
   *  $field_map takes precedence, it will overwrite previously set values
   *      
   *  @param  array $field_map      
   *  @return object  the class instance for fluid interface
   */
  public function addFields(array $field_map);
  
  /**
   *  set all the fields in $field_map to the instance field_map
   *  
   *  @since  6-3-11   
   *  @param  array $field_map      
   *  @return object  the class instance for fluid interface
   */
  public function setFields(array $field_map);
  
  /**
   *  return the instance's field_map
   *  
   *  @return array
   */
  public function getFields();
  
  /**
   *  bump the field at $key by $count
   *  
   *  @since  5-26-10
   *      
   *  @param  string  $key  the name
   *  @param  integer $count  the value to increment $key
   *  @return integer the incremented value now stored at $key
   */
  public function bumpField($key,$count);

}//class     
