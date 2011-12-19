<?php
/**
 *  wrapper around Symfony's Response object (no sense in re-inventing the wheel)
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-30-11
 *  @package montage
 ******************************************************************************/
namespace Montage\Response;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\Cookie;

use Montage\Field\Fieldable;
use Montage\Field\Escape;

class Response extends SymfonyResponse implements Fieldable {

  const CONTENT_HTML = 'text/html';
  const CONTENT_TXT = 'text/plain';
  const CONTENT_JS = 'text/javascript'; // application/javascript, application/x-javascript
  const CONTENT_CSS = 'text/css';
  const CONTENT_JSON = 'application/json';
  const CONTENT_JSON_HEADER = 'application/x-json';
  const CONTENT_XML = 'text/xml'; // application/xml, application/x-xml
  const CONTENT_RDF = 'application/rdf+xml';
  const CONTENT_ATOM = 'application/atom+xml';

  /**
   *  holds the key/value mapping for different tags of the feed
   *  
   *  @var  array
   */
  protected $field_map = array();

  /**
   *  sets the unauthorized 401 headers for prompting http auth authentication
   *
   *  it is up to you to send them using {@link parent::sendHeaders()} or letting 
   *  the script run its course      
   *      
   *  @since  9-6-11
   *  @param  string  $realm  you can really put anything here I think, it seems to be the message
   *                          that will be included in the http auth prompt         
   */
  public function setUnauthorized($realm = ''){
  
    /// header('WWW-Authenticate: Basic realm="API"'); 
    /// header('HTTP/1.0 401 Unauthorized');
  
    $this->setHeader('WWW-Authenticate',sprintf('Basic realm="%s"',$realm));
    $this->setStatusCode(401);
  
  }//method

  /**
   *  send the http response headers
   *  
   *  overrides the parent to not attempt to send the headers if there was output before this
   *  method was called
   *  
   *  @since  6-30-11         
   */
  public function sendHeaders(){
  
    // canary...
    if(headers_sent()){ return; }//if
  
    return parent::sendHeaders();
  
  }//method

  /**
   *  true if content isn't empty
   *  
   *  @return boolean      
   */
  public function hasContent(){ return !empty($this->content); }//method

  /**
   *  set a header
   *  
   *  @param  string  $key  the header key (the key is on the left of the colon, eg Content-Type)
   *  @param  string  $val  the header value         
   */
  public function setHeader($key,$val){ return $this->headers->set($key,$val); }//method
  
  /**
   *  get a header
   *  
   *  @param  string  $key  the header you want
   *  @param  mixed $default_val  what will be returned if the header doesn't exist
   *  @return mixed usually a string though    
   */
  public function getHeader($key,$default_val = null){
    return $this->headers->set($key,$default_val);
  }//method
  
  /**
   *  true if a header exists and is set
   *  
   *  @param  string  $key  the header you want
   *  @return boolean   
   */
  public function hasHeader($key){ return $this->headers->has($key); }//method

  /**
   *  the content type header
   */
  public function setContentType($val){ return $this->setHeader('Content-Type',$val); }//method
  public function getContentType(){ return $this->getHeader('Content-Type',self::CONTENT_HTML); }//method
  public function hasContentType(){ return $this->hasHeader('Content-Type'); }//method
  
  /**
   *  hold the template the response will use to render the response
   */
  function setTemplate($val){ return $this->setField('template',$val); }//method
  function getTemplate(){ return $this->getField('template',''); }//method
  function hasTemplate(){ return $this->hasField('template'); }//method
  function killTemplate(){ return $this->killField('template'); }//method
  
  /**
   *  hold the title
   */
  function setTitle($val){ return $this->setField('title',$val); }//method
  function getTitle(){ return $this->getField('title',''); }//method
  function hasTitle(){ return $this->hasField('title'); }//method

  /**
   *  hold the description
   */
  function setDesc($val){ return $this->setField('desc',$val); }//method
  function getDesc(){ return $this->getField('desc',''); }//method
  function hasDesc(){ return $this->hasField('desc'); }//method
  
  /**
   *  set the $val into $key
   *  
   *  @param  string  $key
   *  @param  mixed $val
   *  @return object  the class instance for fluid interface
   */
  public function setField($key,$val = null){
    $this->field_map[$key] = $val;
    return $this;
  }//method
  
  /**
   *  check if $key exists and is non-empty
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function hasField($key){ return !empty($this->field_map[$key]); }//method
  
  /**
   *  check if $key exists
   *  
   *  @param  string  $key   
   *  @return  boolean
   */
  public function existsField($key){ return array_key_exists($key,$this->field_map); }//method
  
  /**
   *  return the value of $key, return $default_val if key doesn't exist
   *
   *  @param  string  $key
   *  @param  mixed $default_val
   *  @return mixed
   */
  public function getField($key,$default_val = null){
    return $this->existsField($key) ? $this->field_map[$key] : $default_val;
  }//method
  
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
    if($this->hasField($key)){
      $ret_val = $this->field_map[$key];
      unset($this->field_map[$key]);
    }//if
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
  
    if(!empty($field_map)){
      $this->field_map = array_merge($this->field_map,$field_map);
    }//if
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
  
    $this->field_map = $field_map;
    return $this;
  
  }//method
  
  /**
   *  return the instance's field_map
   *  
   *  @return array
   */
  public function getFields(){ return $this->field_map; }//method
  
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
  public function hasFields(){ return !empty($this->field_map); }//method

}//class
