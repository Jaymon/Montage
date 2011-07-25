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
use Montage\Field\GetFieldable;

class Request extends SymfonyRequest implements MontageRequest,GetFieldable {

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
   *  get the browser's user agent string
   *  
   *  @return string  the user agent (eg, Mozilla/5.0 (Windows; U; Windows NT 5.1;) Firefox/3.0.17)
   */
  public function getUserAgent(){ return $this->server->get('HTTP_USER_AGENT',''); }//method
  
  /**
   *  Returns true if the request is an XMLHttpRequest.
   *
   *  It works if your JavaScript library set an X-Requested-With HTTP header.
   *  Works with Prototype, Mootools, jQuery, and perhaps others or if ajax_request
   *  was passed in
   *
   *  @return bool true if the request is an XMLHttpRequest, false otherwise
   */
  public function isAjax(){
    return $this->isXmlHttpRequest() || $this->existsField('ajax_request');
  }//method
  
  /**
   *  shortcut method to know if this is a POST request
   *  
   *  @return boolean
   */
  public function isPost(){ return $this->isMethod('POST'); }//method
  
  /**
   *  true if the passed in $method is the same as the request method
   *  
   *  @param  string  $method
   *  @return boolean
   */
  public function isMethod($method){
    return $this->getMethod() === mb_strtoupper($method);
  }//method
  
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
   *  return true if there are fields
   *  
   *  @since  6-30-11   
   *  @return boolean
   */
  public function hasFields(){
    
    $fields = $this->getFields();
    return !empty($fields);
    
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
    
      $ret_mixed = $this->query->get($key,$default_val);
    
    }//if/else
  
    return $ret_mixed;
  
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
   *  return the instance's field_map
   *  
   *  @return array
   */
  public function getFields(){
  
    return array_merge($this->query->all(),$this->request->all());
  
  }//method

}//class
