<?php
/**
 *  thin wrapper around Symfony's Request object (no sense in reinventing the wheel)
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 4-6-10
 *  @package montage
 ******************************************************************************/
namespace Montage\Request;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Montage\Request\Requestable as MontageRequest;
use Montage\Field\Fieldable;

class Request extends SymfonyRequest implements MontageRequest,Fieldable {

  ///public function __construct(){
  
    ///parent::__construct($_GET, $_POST, array(), $_COOKIE, $_FILES, $_SERVER);
  
  ///}//method

  /**
   *  return the full requested url
   *
   *  @since  6-29-11   
   *  @return string   
   */
  public function getUrl(){ return $this->getUri(); }//method
  
  /**
   *  return the base requested url
   *  
   *  the base url is the requested url minus the requested path
   *      
   *  @since  6-29-11         
   *  @return string
   */
  public function getBase(){ return $this->getScheme().'://'.$this->getHttpHost().$this->getBaseUrl(); }//method

  /**
   *  gets just the request path
   *  
   *  @example
   *    http://example.com/var/web/foo/bar return foo/bar         
   *    http://example.com/foo/bar return foo/bar
   *       
   *  @return string  just the request path without the root path
   */
  public function getPath(){ return $this->getPathInfo(); }//method
  
  /**
   *  shortcut method for you to know if this is a command line request
   *  
   *  @return boolean
   */
  function isCli(){ return (strncasecmp(PHP_SAPI, 'cli', 3) === 0); }//method
  
  /**
   *  set the $val into $key
   *      
   *  @param  string  $key
   *  @param  mixed $val
   *  @return object  the class instance for fluid interface
   */
  public function setField($key,$val = null){
    throw new \BadMethodCallException(sprintf('%s unsupported in this context',__METHOD__));
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function hasField($key){
  
    $mixed = $this->getField($key,null);
    return !empty($mixed);
  
  }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function existsField($key){ return $this->query->has($key) || $this->request->has($key); }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  public function getField($key,$default_val = null){
  
    $ret_mixed = $default_val;
    if($this->request->has($key)){
      $ret_mixed = $this->request->get($key);
    }else{
    
      $ret_mixed = $this->query->get($default_val);
    
    }//if/else
  
    return $ret_mixed;
  
  }//method
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return object  the class instance for fluid interface
   */
  public function killField($key){
    throw new \BadMethodCallException(sprintf('%s unsupported in this context',__METHOD__));
  }//method
  
  /**
   *  check's if a field exists and is equal to $val
   *  
   *  @param  string  $key  the name
   *  @param  string  $val  the value to compare to the $key's set value
   *  @return boolean
   */
  public function isField($key,$val){
  
    return ($this->getField($key) === $val);
  
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
    throw new \BadMethodCallException(sprintf('%s unsupported in this context',__METHOD__));
  }//method
  
  /**
   *  set all the fields in $field_map to the instance field_map
   *  
   *  @since  6-3-11   
   *  @param  array $field_map      
   *  @return object  the class instance for fluid interface
   */
  public function setFields(array $field_map){
    throw new \BadMethodCallException(sprintf('%s unsupported in this context',__METHOD__));
  }//method
  
  /**
   *  return the instance's field_map
   *  
   *  @return array
   */
  public function getFields(){
  
    return array_merge($this->query->all(),$this->request->all());
  
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
  public function bumpField($key,$count = 1){
    throw new \BadMethodCallException(sprintf('%s unsupported in this context',__METHOD__));
  }//method

}//class
