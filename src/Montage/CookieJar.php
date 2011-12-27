<?php
/**
 *  handle cookies 
 *
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 12-19-11
 *  @package montage
 ******************************************************************************/
namespace Montage;

use Montage\Field\Field;
use Symfony\Component\HttpFoundation\Cookie;

class CookieJar extends Field {

  /**
   *  @var  \Montage\Request\Requestable
   */
  public $request = null;
  
  /**
   *  @var  \Montage\Response\Response
   */
  public $response = null;
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function hasField($key){
  
    $cookie_map = $this->request->cookies->all();
    return !empty($cookie_map[$key]);
  
  }//method
  
  /**
   *  return true if there are fields
   *  
   *  @since  6-30-11   
   *  @return boolean
   */
  public function hasFields(){
  
    $cookie_map = $this->request->cookies->all();
    return !empty($cookie_map);
  
  }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function existsField($key){ return $this->request->cookies->has($key); }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  public function getField($key,$default_val = null){
  
    return $this->request->cookies->get($key,$default_val);
  
  }//method
  
  /**
   *  set the $val into $key
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return object  the class instance for fluid interface
   */
  public function setField($key,$val = null){
  
    $cookie = new Cookie($key,$val);
    return $this->setCookie($cookie);
  
  }//method
  
  /**
   *  remove $key and its value from the map
   *  
   *  @param  string  $key
   *  @return object  the class instance for fluid interface
   */
  public function killField($key){
  
    $this->response->headers->clearCookie($key);
    return $this;
    
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
  
    foreach($field_map as $key => $val){
    
      $this->setField($key,$val);
    
    }//foreach
    
    return $this;
  
  }//method
  
  /**
   *  remove all the previously set cookies and replace them with $field_map
   *  
   *  @since  6-3-11   
   *  @param  array $field_map      
   *  @return object  the class instance for fluid interface
   */
  public function setFields(array $field_map){
  
    $cookie_list = $this->getResponseFields();
    foreach($cookie_list as $cookie){
    
      $this->response->headers->removeCookie($cookie->getName(),$cookie->getPath(),$cookie->getDomain());
    
    }//foreach
  
    return $this->addFields($field_map);
  
  }//method
  
  /**
   *  return the instance's field_map
   *  
   *  @return array
   */
  public function getFields(){ return $this->request->cookies->all(); }//method
  
  /**
   *  set a cookie
   *  
   *  @since  9-6-11
   *  @param  Cookie  $cookie the cookie object
   */
  public function setCookie(Cookie $cookie){ return $this->response->headers->setCookie($cookie); }//method
  
  /**
   *  get the request cookies, these are the cookies from the browser
   *  
   *  @return array      
   */
  public function getRequestFields(){ return $this->request->cookies->all(); }//method
  
  /**
   *  get the response cookies, the are the cookies that will be sent to the visitor
   *  
   *  @return array
   */
  public function getResponseFields(){ return $this->response->headers->getCookies(); }//method
  
  ///protected function encodeField($val){}//method
  
  ///protected function decodeField($val){}//method

}//class
