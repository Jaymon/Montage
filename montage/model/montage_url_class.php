<?php

/**
 *  class for generating urls
 *
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-22-10
 *  @package mingo 
 ******************************************************************************/
class montage_url extends montage_base {

  const URL_SEP = '/';
  
  const SCHEME_NORMAL = 'http://';
  const SCHEME_SECURE = 'https://';

  /**
   *  
   */
  final function __construct(){
    $this->start();
  }//method
  
  function start(){}//method
  
  /**
   *  usually something like: http:// or https://
   */        
  function setScheme($val){ return $this->setField('montage_url_scheme',$val); }//method
  function getScheme(){ return $this->getField('montage_url_scheme',self::SCHEME_NORMAL); }//method
  
  
  /**
   *  magically allow this class set fields using the method call
   *  
   *  @param  string  $method the method that was called
   *  @param  array $args the arguments passed into the $method call         
   *  @return mixed
   */
  function __call($method,$args){
  
    $method_map = array(
      'set' => 'handleSet',
      'get' => 'handleGet',
      'has' => 'handleHas'
    );
    
    return $this->getCall($method_map,$method,$args);
  
  }//method
  
  protected function handleHas($field,$args = array()){ return $this->hasField($field); }//method
  
  /**
   *  same everything as {@link get()} but will pass in the $field as the $root
   *  
   *  @param  string  $field
   *  @param  mixed $args the same args as {@link get()} can accept
   *  @return string   
   */
  protected function handleGet($field,$args = array()){
  
    $root = $this->getField($field,'');
    return call_user_func_array(array($this,'get'),$args);
  
  }//method
  
  /**
   *  handle setting a custom url for a field
   *  
   *  @example
   *    // make foo point to /bar/ url...
   *    $this->setFoo(self::SCHEME_NORMAL,'example.com','bar');
   *    $this->getFoo(); // -> http://example.com/bar       
   *
   *  @param  string  $field
   *  @param  array $args the arguments, can be up to 3 arguments passed in:
   *                        1 = path (eg, /foo/bar/))
   *                        2 = host (eg, example.com), path
   *                        3 = scheme (eg, one of the SCHEME_* constants), host, path            
   *  @return string
   */
  protected function handleSet($field,$args){
  
    // canary...
    if(empty($args[0])){
      throw new RuntimeException(
        'cannot set with an empty $args array. Any set* methods can take up to 3 arguments: '
        .' 1 argument: [path (eg, /foo/bar)], 2 arguments: [host (eg, example.com), path], or '
        .' 3 arguments: [scheme (eg, http), host, path].'
      );
    }//if 
  
    $total_args = count($args);
    
    if($total_args === 1){
    
      $ret_str = $args[0];
      $this->setField($field,$ret_str);
    
    }else{
      
      $scheme = $host = $path = '';
      $list = array();
    
      if($total_args <= 2){
      
        $host = $args[0];
        if(!$this->hasScheme($host)){
          $scheme = $this->getScheme();
        }//if
        
        $path = $args[1];
        
      }else if($total_args >= 3){
      
        $scheme = $args[0];
        if(!$this->hasScheme($scheme)){
          $scheme = sprintf('%s://',$scheme);
        }//if
        
        $host = $args[1];
        $path = $args[2];
      
      }//if/else if
    
      if(!empty($host)){
        $host = rtrim($host,self::URL_SEP);
        $host = sprintf('%s%s',$scheme,$host);
        $list[] = $host;
      }//if
      
      if(!empty($path)){
        $path = ltrim($path,self::URL_SEP);
        $list[] = $path;
      }//if
      
      $ret_str = join(self::URL_SEP,$list);
      
      $this->setField($field,$ret_str);
    
    }//if/else
  
    return $ret_str;
  
  }//method
  
  /**
   *  true if $val has something like http:// on the front
   *  
   *  @param  string  $val
   *  @return boolean
   */
  protected function hasScheme($val){
    return empty($val) ? false : (preg_match('#^\w://#',$val) ? true : false);
  }//method

}//class     
