<?php
/**
 *  handle session stuff 
 *
 *  to start this and check for headers:
 *
 *  $file = $line = '';
 *  if(!headers_sent($file,$line)){ $this->start(); }//if
 *
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-28-10
 *  @package montage
 ******************************************************************************/
namespace Montage;

use Montage\Field\Fieldable;
use Montage\Field\Escape;

use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

class Session extends SymfonySession implements Fieldable {

  /**
   *  set the $val into $key
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return object  the class instance for fluid interface
   */
  public function setField($key,$val = null){
  
    $this->set($key,$val);
    return $this;
    
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function hasField($key){
    $val = $this->get($key, null);
    return !empty($val);
  }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function existsField($key){ return $this->has($key); }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  public function getField($key,$default_val = null){ return $this->get($key,$default_val); }//method
  
  /**
   *  return the value of getField, but wrap it in an escape object
   *  
   *  this is useful for making sure user submitted input is safe
   *
   *  @see  getField()      
   */
  public function escField($key,$default_val = null){
    
    return new Escape($this->getField($key,$default_val));
    
  }//method
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return object  the class instance for fluid interface
   */
  public function killField($key){
  
    $this->remove($key);
    return $this;
    
  }//method
  
  /**
   *  check's if a field exists and is equal to $val
   *  
   *  @param  string  $key  the name
   *  @param  string  $val  the value to compare to the $key's set value
   *  @return boolean
   */
  public function isField($key,$val){
    $ret_bool = false;
    if($this->existsField($key)){
      $ret_bool = $this->getField($key) == $val;
    }//if
    return $ret_bool;
  }//method
  
  /**
   *  add all the fields in $field_map to the instance field_map
   *  
   *  $field_map takes precedence, it will overwrite previously set values
   *      
   *  @param  array $field_map      
   *  @return object  the class instance for fluid interface
   */
  public function addFields(array $field_map){

    // canary
    if(empty($field_map)){ return $this; }//if

    $field_map = array_merge($this->all(), $field_map);
    $this->replace($field_map);
    return $this;
  
  }//method
  
  /**
   *  set all the fields in $field_map to the instance field_map
   *  
   *  @since  6-3-11   
   *  @param  array $field_map      
   *  @return object  the class instance for fluid interface
   */
  public function setFields(array $field_map){
  
    $this->replace($field_map);
    return $this;
    
  }//method
  
  /**
   *  return the instance's field_map
   *  
   *  @return array
   */
  public function getFields(){ return $this->all(); }//method
  
  /**
   *  bump the field at $key by $count
   *  
   *  @since  5-26-10
   *      
   *  @param  string  $key  the name
   *  @param  integer $count  the value to increment $key
   *  @return integer the incremented value now stored at $key
   */
  public function bumpField($key,$count = 1){
    
    $val = $this->getField($key,0);
    $val += $count;
    $this->setField($key,$val);
    return $val;
    
  }//method
  
  /**
   *  return true if there are fields
   *  
   *  @since  6-30-11   
   *  @return boolean
   */
  public function hasFields(){
    $field_map = $this->all();
    return !empty($field_map);
  }//method

  /**
   * just making it easier to check if a key exists in any field type
   *
   * @since 2013-3-13
   * @param string  $key  the key to check in all the field types
   * @return  boolean
   */
  public function hasAny($key){
    $val = $this->getAny($key);
    return !empty($val);
  }//method
  
  /**
   *  get a field from any part of the session
   *  
   *  order of precedence is normal session field, then "get" flash session field, then "set"
   *  flash session field, then default_val   
   *  
   *  @since  8-15-10   
   *  @param  string  $key
   *  @param  mixed $default_val  if $key wasn't found, return this
   *  @return mixed
   */
  public function getAny($key,$default_val = null){
  
    $ret_mixed = $default_val;
    if($this->existsField($key)){
      $ret_mixed = $this->getField($key);
    }else if($this->getFlashBag()->has($key)){
      $ret_mixed = $this->getFlashBag()->get($key);
    }//if/else if
  
    return $ret_mixed;
  
  }//method
  
  /**
   *  save the $_GET and $_POST arrays into a session field
   *  
   *  set might be better moved to response when redirect() is called while load might
   *  be better off in request, the key is figuring out when to load. I'm thinking urls
   *  to see if something needs to be loaded
   */
  public function setRequest(){
  
    $field_map = array();
    if(!empty($_GET)){
      $field_map['_GET'] = empty($field_map['_GET']) ? $_GET : array_merge($field_map['_GET'],$_GET);
    }//if
    if(!empty($_POST)){
      $field_map['_POST'] = empty($field_map['_POST']) ? $_POST : array_merge($field_map['_POST'],$_POST);
    }//if
    if(!empty($field_map)){ $this->getFlashBag()->set('montage_session::request_saved',$field_map); }//if
  
  }//method
  
  /**
   *  delete the current session
   *
   *  @since  11-9-11   
   */
  public function kill(){
  
    return $this->invalidate();
  
  }//method
  
  /**
   *  restore get and post vars that could've been set with {@link setRequest()}
   */
  protected function loadRequest(){
    
    $field_map = $this->getFlashBag()->get('montage_session::request_saved',array());
    
    if(!empty($field_map)){
      
      if(!empty($field_map['_GET'])){
        foreach($field_map['_GET'] as $key => $val){
          // only reset the value if isn't set...
          if(!isset($_GET[$key])){ $_GET[$key] = $val; }//if
        }//foreach
      }//if
      
      if(!empty($field_map['_POST'])){
        foreach($field_map['_POST'] as $key => $val){
          // only reset the value if isn't set...
          if(!isset($_POST[$key])){ $_POST[$key] = $val; }//if
        }//foreach
      }//if
      
    }//if
    
  }//method
  
}//class
