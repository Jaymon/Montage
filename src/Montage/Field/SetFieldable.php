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

interface SetFieldable {

  /**
   *  set the $val into $key
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return object  the class instance for fluid interface
   */
  public function setField($key,$val = null);
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return object  the class instance for fluid interface
   */
  public function killField($key);
  
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
   *  bump the field at $key by $count
   *  
   *  @since  5-26-10
   *      
   *  @param  string  $key  the name
   *  @param  integer $count  the value to increment $key
   *  @return integer the incremented value now stored at $key
   */
  public function bumpField($key,$count = 1);

}//class     
