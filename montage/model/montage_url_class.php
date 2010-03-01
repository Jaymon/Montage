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
  
  const SCHEME_NORMAL = 'http';
  const SCHEME_SECURE = 'https';

  /**
   *  
   */
  final function __construct(){
    $this->start();
  }//method

  /**
   *  get a url
   *  
   *  you can also append stuff to the path, and also append get vars by adding an array at
   *  the end:
   *  get($base,'foo','bar',array('getfoo' => 'getbar');
   *  
   *  if you don't include a root as the first argument, everything else will
   *  be appended to the current url
   *  
   *  @param  mixed $arg,...  first argument can be a root last argument can be
   *                          an array of key/val mappings that will be turned into
   *                          a query string                                           
   *  @return string  a complete url will the passed in args used to build it
   */
  function get(){
  
    $args = func_get_args();
    list($url,$path_list,$var_map) = $this->parse($args);
    
    return $this->build($url,$path_list,$var_map);
  
  }//method
  
  /**
   *  get the host of the site (eg, http://example.com)
   *  
   *  like most of the other get* methods, you can pass in path and query vars
   *  
   *  @param  mixed $arg,...
   *  @return string  the host with any args attached to it
   */
  function getHost(){
  
    $args = func_get_args();
    list($path_list,$var_map) = $this->sortArgs($args);
    $request = montage::getRequest();
    
    return $this->build($request->getBase(),$path_list,$var_map);
  
  }//method
  
  /**
   *  same as {@link get()} but it strips out any get vars that would have been appended
   *  with the question mark. this is handy when you want to use the current url but you
   *  don't want the query vars that are already on the current url
   *  
   *  @example
   *    $url->get(); // -> http://example.com/foo/?bar=che
   *    $url->getBase(); // -> http://example.com/foo/
   *    $url->getBase(array('che' => 'bar')); // -> http://example.com/foo/?che=bar   
   *  
   *  @see  get()
   *  @return string  the base url with no ?key=val... string (unless one was passed in)   
   */
  function getBase(){
  
    $args = func_get_args();
    list($url,$var_list,$var_map) = $this->parse($args);
    list($url,$query_var_map) = $this->getSplit($url);
    return self::build($url,$var_list,$var_map);
  
  }//method
  
  /**
   *  adds query vars to the given url.
   *  
   *  this will url encode the query vars, it will also add any vars that were set
   *  in {@link $get_vars} with {@link setVar()}
   *  
   *  @param  string  $url  the url to append the vars to
   *  @param  array $var_map the vars that will be appended in key => val mappings
   *  @return string  the url with the vars attached
   */
  function getAppended($url,$var_map = array()){
    
    // sanity...
    if(empty($var_map)){ return $url; }//if
    
    list($url,$query_vars) = $this->getSplit($url);
    if(!empty($query_vars)){
    
      $var_map = array_merge($query_vars,$var_map);
    
    }//if/else
    
    $var_str = http_build_query($var_map,'','&');
    if(!empty($var_str)){ $url .= '?'.$var_str; }//if
    
    return $url;
    
  }//method
  
  /**
   *  split a string into base and query vars
   *  
   *  @param  string  $url
   *  @return array array($url,$query_vars) where $url is the url without a ?... string, and $query_vars
   *                is an array of the key/value pairs the $url originally contained
   */
  function getSplit($url){
    
    // canary...
    if(empty($url)){ return array('',array()); }//if
    
    $query_vars = array();
    
    $query_str_start = mb_strpos($url,'?');
    if($query_str_start !== false){
    
      // the url does have a query string, so parse it and then merge it into the url...
      
      $url_query_str = mb_substr($url,$query_str_start + 1);
      $url = mb_substr($url,0,$query_str_start); // we just want the regular url
      
      parse_str($url_query_str,$query_vars);
      $query_vars = montage_text::getSlashless($query_vars);
      
    }//if
  
    return array($url,$query_vars);
    
  }//method
  
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
  
    $base = $this->getField($field,'');
    array_unshift($args,$base);
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
    
      $request = montage::getRequest();
      $ret_str = $args[0];
      $this->setField($field,$this->assemble('',$request->getBase(),$ret_str));
    
    }else if($total_args < 3){
    
        $ret_str = $this->assemble('',$args[0],$args[1]);
        
    }else{
      
      $ret_str = $this->assemble($args[0],$args[1],$args[2]);
      
    }//if/else if/else
      
    $this->setField($field,$ret_str);
    
    return $ret_str;
  
  }//method
  
  /**
   *  this will convert a list of $args into a base url and all the stuff that
   *  will be appended to the base url
   *  
   *  @param  array $args
   *  @param  boolean $ignore_current_get_vars  see {@link getCurrentMap()} for explanation
   *  @return array array($url_map,$var_list,$var_map) where $url_map has keys: 'url', 'module', and 'action'
   *                and $var_list is all the path elements (eg, array('foo','bar' would become: /foo/bar)
   *                and $var_map are the get vars (eg, array('foo' => 'bar') would become ?foo=bar)      
   */
  protected function parse($args,$ignore_current_get_vars = false){
  
    // canary...
    if(empty($args)){ return array($this->get(),array(),array()); }//if
    
    $ret_url = '';
    list($ret_list,$var_map) = $this->sortArgs($args);
    $base = empty($ret_list[0]) ? '' : $ret_list[0];
    
    if(!empty($base)){
    
      if(montage_text::isUrl($base)){
      
        $ret_url = $base;
        $ret_list = array_slice($ret_list,1);
        
      }else if($base[0] === '/'){
      
        $ret_url = $this->assemble(self::SCHEMA_NORMAL,$this->getHost(),$base);
        $ret_list = array_slice($ret_list,1);
      
      }else{
      
        $ret_url = $this->gethost();
      
      }//if/else if/else
    
    }else{
    
      $ret_url = montage::getRequest()->getUrl();
    
    }//if/else

    return array($ret_url,$ret_list,$var_map);
  
  }//method
  
  /**
   *  most of the get methods can take a variable string of arguments, with an array at the
   *  end that will be turned into a get parameter string, this separates the path vars (eg, foo/bar)
   *  from the parameter vars (eg, ?foo=bar) and returns them
   *  
   *  @param  array $args an array of path/parameters
   *  @return array($path_vars,$get_vars)
   */              
  protected function sortArgs($args){
    
    // canary...
    if(empty($args)){ return array(array(),array()); }//if
    
    $ret_list = $ret_var_map = array();
    
    // if the last element is an array, then it is a var_map, else it is just a normal value...
    $ret_var_map = end($args);
    if(is_array($ret_var_map)){
      // the last element isn't part of the url var list, it contains the get vars...
      $ret_list = array_slice($args,0,-1);
    }else{
      $ret_list = $args;
      $ret_var_map = array();
    }//if/else
    
    return array($ret_list,$ret_var_map);
    
  }//method
  
  /**
   *  given a $scheme, $host, $path or combination of the 3 build a url
   *  
   *  @param  string  $scheme can be one of the SCHEME_* constants
   *  @param  string  $host something like example.com
   *  @param  string  $path something like /foo/bar
   *  @return string  all the 3 parst combined
   */
  protected function assemble($scheme,$host,$path = ''){
    
    if(empty($host)){
    
      $host = $this->getHost();
    
    }//if/else
    
    $url_bits = empty($host) ? array() : parse_url($host);
    
    if(montage_text::isUrl($host)){
    
      if(empty($scheme)){
      
        $scheme = $url_bits['scheme'];
        $host = sprintf(
          '%s%s',
          $url_bits['scheme'],
          isset($url_bits['path']) ? rtrim($url_bits['path'],self::URL_SEP) : ''
        );
        
      }//if
    
    }else{
    
      // get rid of the :// because we are going to add it later and don't want to
      // add it twice...
      $scheme = empty($scheme) ? self::SCHEME_NORMAL : rtrim($scheme,':/');
    
    }//if/else
  
    $list = array();
  
    if(!empty($host)){
      $host = rtrim($host,self::URL_SEP);
      $host = sprintf('%s://%s',$scheme,$host);
      $list[] = $host;
    }//if
    
    if(!empty($path)){
      $path = ltrim($path,self::URL_SEP);
      $list[] = $path;
    }//if
    
    $ret_str = join(self::URL_SEP,$list);
    return $ret_str;
  
  }//method
  
  /**
   *  build a specific url
   *  build works by adding all arguments to the base url, if the last argument is 
   *  an array, those values will be added as get variables (ie, build('one','two',array('this' => 'that'))
   *  would turn into "[base]/one/two/?this=that), likewise, if the last non-array var has a # as the first
   *  char, it will become a fragment on the url before the get vars
   *  
   *  @since  9-6-08
   *      
   *  @param  string  $base the base url to start from
   *  @param  array $var_list the vars to add to the base
   *  @param  array $var_map  the get vars to append to the end of the url build with $bars and $var_list   
   *  @return string  the completely built url
   */
  protected function build($base,$var_list,$var_map = array()){
    
    // sanity...
    if(empty($var_list) && empty($var_map)){ return $base; }//if
    if(!is_array($var_list)){ $var_list = array($var_list); }//if
    
    $ret_str = '';
    
    $base_bits = parse_url($base);
    
    $ret_str .= empty($base_bits['scheme']) ? '' : $base_bits['scheme'].'://'; 
    $ret_str .= empty($base_bits['host']) ? '' : $base_bits['host'];
    $ret_str .= empty($base_bits['path']) ? '' : $base_bits['path'];
    $ret_str .= (mb_substr($ret_str,-1) == self::URL_SEP) ? '' : self::URL_SEP;
    
    $query_str = empty($base_bits['query']) ? '' : '?'.$base_bits['query'];
    
    // add mod_rewrite url vars to the end of the url if there are any...
    if(!empty($var_list)){
    
      // handle the fragment...
      $var_list = array_filter(
        array_map('mb_strtolower',
          array_map(
            'trim',
            $var_list,
            array_fill(0,count($var_list),'/')
          )
        )
      );
      $last = end($var_list);
      if(!empty($last)){
      
        if($last[0] != '#'){
        
          $ret_str .= join(self::URL_SEP,$var_list);
          if(mb_strrpos($last,'.') === false){
            $ret_str .= self::URL_SEP;
          }//if
          
        }else{
          
          // the last element is a fragment...
          $real_var_list = array_slice($var_list,0,-1);
          $ret_str .= join(self::URL_SEP,$real_var_list);
          $base_bits['fragment'] = $last;
          $last = end($real_var_list);
          
        }//if/else
      }//if
    }//if
    
    $ret_str .= $query_str; // add any query string back on
    
    // add any get vars to the url...
    $ret_str = $this->getAppended($ret_str,$var_map);
    
    // fragment should always be at the end...
    $ret_str .= empty($base_bits['fragment']) ? '' : $base_bits['fragment'];
    
    return $ret_str;
    
  }//method

}//class     
