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

class Request extends SymfonyRequest implements Requestable,GetFieldable {

  /**
   *  create instance
   *
   *  @since  7-25-11
   *  @param  array $cli  the argv params passed in from the command line, this is at the end to ensure
   *                      compatibility with \Symfony\Component\HttpFoundation\Request::create() (it uses static::
   *                      and so the vars have to be in the right place      
   *  @see  parent::__construct for all the other params      
   */
  public function __construct(array $query = array(), array $request = array(), array $attributes = array(), array $cookies = array(), array $files = array(), array $server = array(), $content = null, array $cli = array()){
  
    $cli_query = array();
  
    if(!empty($cli)){
    
      $cli_query = $this->parseArgv($cli);

      // treat all the key/vals as query vars...
      if(!empty($cli_query['map'])){
      
        $query = array_merge($query,$cli_query['map']);
      
      }//if
      
    }//if
  
    parent::__construct($query,$request,$attributes,$cookies,$files,$server,$content);
    
    // treat any cli vals as the path...
    if(!empty($cli_query['list'])){
    
      $this->pathInfo = join('/',$cli_query['list']);
    
    }//if
    
  }//method

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
      
  /**
   *  function to make passing arguments passed into the CLI easier
   *  
   *  an argument has to be in the form: --name=val or --name if you want name to be true
   *  
   *  if you want to do an array, then specify the name multiple times: --name=val1 --name=val2 will
   *  result in ['name'] => array(val1,val2)
   *  
   *  we don't use php's http://php.net/manual/en/function.getopt.php because inputs are variable
   *  and we don't know what they will be before hand. Really, I'm shocked at how unflexible php's
   *  built in solution is                  
   *  
   *  @example
   *    // command line: php test.php --foo=bar baz che
   *    $this->parseArgv($_SYSTEM['argv']); // array('list' => array('baz,'che'), 'map' => array('foo' => 'bar'))      
   *      
   *  @param  array $argv the values passed into php from the commmand line
   *  @param  array $required_argv_map hold required args that need to be passed in to be considered valid.
   *                                  The name is the key and the required value will be the val, if the val is null
   *                                  then the name needs to be there with a value (in $argv), if the val 
   *                                  is not null then that will be used as the default value if 
   *                                  the name isn't passed in with $argv 
   *  @return array array has 2 indexes: 'list' and 'map' where list contains all args that weren't in
   *                the form --name=val (ie, they are in the form val) and map contains all --name=val   
   */
  public static function parseArgv($argv,$required_argv_map = array())
  {
    // canary...
    if(empty($argv)){ return array(); }//if
  
    $ret_list = array();
    $ret_map = array();
  
    // do some hackish stuff to decide if the first argv needs to be stripped...
    $bt = debug_backtrace();
    
    // we want to look at the file that started the request...
    end($bt);
    $key = key($bt);
    
    if(!empty($bt[$key]['file'])){
    
      $file_path = $bt[$key]['file'];
      $file_name = basename($bt[$key]['file']);
    
      if(($argv[0] == $file_path) || ($argv[0] == $file_name)){
        $argv = array_slice($argv,1);
      }//if

    }//if
  
    foreach($argv as $arg){
    
      // canary...
      if((!isset($arg[0]) || !isset($arg[1])) || ($arg[0] != '-') || ($arg[1] != '-')){
        
        /* throw new \InvalidArgumentException(
          sprintf('%s does not conform to the --name=value convention',$arg)
        ); */
        
        $ret_list[] = $arg;
        continue;
        
      }//if
    
      $arg_bits = explode('=',$arg,2);
      // strip off the dashes...
      $name = mb_substr($arg_bits[0],2);
      
      $val = true;
      if(isset($arg_bits[1])){
        
        $val = $arg_bits[1];
        
        if(!is_numeric($val)){
          
          // convert literal true or false into actual booleans...
          switch(mb_strtoupper($val)){
          
            case 'TRUE':
              $val = true;
              break;
              
            case 'FALSE':
              $val = false;
              break;
          
          }//switch
        
        }//if
        
      }//if
      
      if(isset($ret_map[$name])){
      
        $ret_map[$name] = (array)$ret_map[$name];
        $ret_map[$name][] = $val;
        
      }else{
      
        $ret_map[$name] = $val;
        
      }//if/else
    
    }//foreach
      
    // make sure any required key/val pairings are there...
    if(!empty($required_argv_map)){
    
      foreach($required_argv_map as $name => $default_val){
      
        if(!isset($ret_map[$name])){
        
          if($default_val === null){
          
            throw new \UnexpectedValueException(
              sprintf(
                '%s was not passed in and is required, you need to pass it in: --%s=[VALUE]',
                $name,
                $name
              )
            );
            
          }else{
          
            $ret_map[$name] = $default_val;
            
          }///if/else
        }//if
      
      }//foreach
    
    }//if
  
    return array('list' => $ret_list,'map' => $ret_map);
  
  }//method

}//class
