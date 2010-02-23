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
   *  this is just here so other constructors can call it but children can override it
   */        
  function start(){}//method

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
   *  $field_map takes precedence, it will overwrite previously set values
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
  
  /**
   *  designed to be called from a __call() magic method, this will decide what
   *  method to call and return the result
   *  
   *  @param  array $method_map a key/val mapping where the key is the $prefix that is
   *                            returned from {@link getSplitMethod()} and the val is
   *                            the internal callback method that will take the $field
   *                            returned from {@link getSplitMethod()} and the $args
   *  @param  string  $method the method that was passed into __call()
   *  @param  array $args the arguments passed into the __call() method
   *  @return mixed whatever is returned from the callback
   *  @throws montage_exception   
   */
  protected function getCall($method_map,$method,$args){
    
    $ret_mix = null;
    list($key,$field) = $this->getSplitMethod($method);
    
    if(empty($method_map[$key])){
    
      throw new montage_exception(sprintf('could not find a match for $method %s with command: %s',$method,$key));
    
    }else{
    
      $callback = $method_map[$key];
      $ret_mix = $this->{$callback}($field,$args);
    
    }//if/else
  
    return $ret_mix;
  
  }//method
  
  /**
   *  splits the $method by the first non lowercase char found
   *  
   *  the reason why we split on the first capital is because if we just did find
   *  first substring that matches in __call(), then something like gt and gte would 
   *  match the same method, so we enforce camel casing (eg, gteEdward and gtEdward) 
   *  so that all method names can be matched. And we use this method across all
   *  __call() using classes to make it consistent.         
   *  
   *  @param  string  $method the method name that was called
   *  @return array array($prefix,$field)
   */
  protected function getSplitMethod($method){
  
    $ret_prefix = $ret_field = '';
  
    // get everything lowercase form start...
    for($i = 0,$max = mb_strlen($method); $i < $max ;$i++){
    
      $ascii = ord($method[$i]);
      if(($ascii < 97) || ($ascii > 122)){
      
        $ret_field = $this->getNormalizedField(mb_substr($method,$i));
        break;
      
      }else{
      
        $ret_prefix .= $method[$i];
      
      }//if/else
    
    }//for
    
    if(empty($ret_field)){
    
      throw new montage_exception(
        'no field was specified in the method, for example, if you want to "get" the field "foo" '.
        'you would do: getFoo() (notice the capital F)'
      ); 
    
    }//if
    
    return array($ret_prefix,$ret_field);
  
  }//method
  
  /**
   *  make the field name consistent
   *  
   *  @param  string  $field  the field name
   *  @return string  the $field, normalized
   */
  protected function getNormalizedField($field){
    
    // canary...
    if(is_numeric($field)){
      throw new mingo_exception(sprintf('an all numeric $field like %s is not allowed',$field));
    }//if
    
    return mb_strtolower((string)$field);
  }//method

}//class     
