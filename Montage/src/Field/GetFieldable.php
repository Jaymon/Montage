<?php
/**
 *  The field is just an easy way to store key/value pairs
 *  
 *  the reason this interface exists is to make sure any class can mimick the field
 *  behavior even if it can't extend the Field class 
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-30-11
 *  @package montage 
 ******************************************************************************/
namespace Montage\Field;

interface GetFieldable {

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
   *  check's if a field exists and is equal to $val
   *  
   *  @param  string  $key  the name
   *  @param  string  $val  the value to compare to the $key's set value
   *  @return boolean
   */
  public function isField($key,$val);
  
  /**
   *  return the instance's field_map
   *  
   *  @return array
   */
  public function getFields();
  
  /**
   *  return true if there are fields
   *  
   *  @since  6-30-11   
   *  @return boolean
   */
  public function hasFields();

}//class     
