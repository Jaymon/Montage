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
use Montage\Request\Requestable;
use Montage\Field\GetFieldable;
use Montage\Field\Escape;

class Request extends SymfonyRequest implements Requestable,GetFieldable {
  
  /**
   *  do custom initialization stuff
   *
   *  @since  7-25-11
   *  @see  parent::initialize for params      
   */
  public function initialize(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null){

    $cli_query = null;

    if(!empty($server['argv'])){
    
      $cli = $server['argv'];
    
      // in a real cli request, the 0 will be the script, in a http request, 0 will be the query string
      // but we only care about argv if it has more than the first item...
    
      if(isset($cli[1])){
      
        $cli_query = new \ParseOpt($cli);
      
        // treat all the key/vals as query vars...
        if($cli_query->hasFields()){
        
          $query = array_merge($query,$cli_query->getFields());
        
        }//if
      
      }//if
      
    }//if
    
    ///\out::e($query, $request, $attributes, $cookies, $files, $server, $content);
    
    parent::initialize($query, $request, $attributes, $cookies, $files, $server, $content);
    
    // treat any cli vals as appendages to the path...
    if(!empty($cli_query) && $cli_query->hasList()){
    
      // this overrides automagic path finding...
      $this->pathInfo = join('/',$cli_query->getList());
    
    }//if
    
  }//method
  
  /**
   *  return true if the request is a local request
   *  
   *  a request is local if it was made from the same computer as the server
   *  
   *  @since  9-6-11
   *  @return boolean
   */
  public function isLocal(){
  
    $remote_addr = $this->server->get('REMOTE_ADDR','');
    return in_array($remote_addr,array('127.0.0.1','::1'));
  
  }//method

  /**
   *  return the full requested url
   *
   *  @since  6-29-11   
   *  @return string   
   */
  public function getUrl(){ return $this->isCli() ? '' : $this->getUri(); }//method
  
  /**
   *  get the browser's user agent string
   *  
   *  @return string  the user agent (eg, Mozilla/5.0 (Windows; U; Windows NT 5.1;) Firefox/3.0.17)
   */
  public function getUserAgent(){ return $this->server->get('HTTP_USER_AGENT',''); }//method
  
  /**
   *  Returns true if the request is an XMLHttpRequest.
   *
   *  It works if your JavaScript library sets an X-Requested-With HTTP header.
   *  Works with Prototype, Mootools, jQuery, and perhaps others or if ajax_request
   *  is passed in as a get/post param
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
  public function getBase(){
  
    // canary...
    if($this->isCli()){ return ''; }//if
    
    return $this->getScheme().'://'.$this->getHttpHost().$this->getBaseUrl();
    
  }//method

  /**
   *  gets just the request path
   *  
   *  @example
   *    http://example.com/var/web/foo/bar return foo/bar because /var/web is the root
   *    http://example.com/foo/bar return foo/bar
   *    http://example.com/foo/bar?che=baz return foo/bar   
   *       
   *  @return string  just the request path without the root path
   */
  public function getPath(){ return $this->getPathInfo(); }//method

  /*
   * get the request type
   *
   * usually this is something like web or command, this is handy for the controller
   * to pick which type of controller should be used
   *
   * @since 10-19-12
   * @return  string  'Web' if a web request, 'Controller' if cli request
   */
  public function getType(){
    return $this->isCommand() ? 'Command' : 'Web';
  }//method
  
  /**
   *  shortcut method for you to know if this is a command line request
   *  
   *  @return boolean
   */
  function isCommand(){
    return !$this->server->has('HTTP_HOST');
    // @note  we can't use the PHP_SAPI because of testing, using the test browser would report a cli request
    // when in actuality it should be treated as a normal http request
    ///return (strncasecmp(PHP_SAPI, 'cli', 3) === 0) || !isset($_SERVER['HTTP_HOST']);
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
