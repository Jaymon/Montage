<?php

/**
 *  class for generating urls
 *
 *  wikipedia uses component for each individual part of a path, I use bit
 *  http://en.wikipedia.org/wiki/Path_%28computing%29 
 *  
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-22-10
 *  @package montage
 ******************************************************************************/
namespace Montage;

use Montage\Field\Field;

class Url extends Field {

  const URL_SEP = '/';
  
  const SCHEME_NORMAL = 'http';
  const SCHEME_SECURE = 'https';
  
  protected $scheme = self::SCHEME_NORMAL;

  /**
   *  create object instance
   *  
   *  @param  string  $current_url  the current url
   *  @param  string  $base_url the url that will be used as the default base               
   */
  public function __construct($current_url = '',$base_url = ''){
  
    $this->setCurrent($current_url);
    $this->setBase($base_url);
    
  }//method
  
  /**
   *  set the current url that will be used in {@link getCurrent()}
   *  
   *  @param  string  $url  the current requested url   
   */
  public function setCurrent($url){ $this->setField('Url.current_url',$this->trimSlash($url)); }//method
  
  /**
   *  set the base url that will be used as the default if no other url is passed into
   *  methods like {@link get()}   
   *
   *  @param  string  $url  the url that will be used as the default base
   */
  public function setBase($url){ $this->setField('Url.base_url',$this->trimSlash($url)); }//method

  /**
   *  get a url
   *  
   *  you can also append stuff to the path, and also append get vars by adding an array at
   *  the end:
   *  get($base,'foo','bar',array('getfoo' => 'getbar');
   *  
   *  if you don't include a root as the first argument, everything else will
   *  be appended to the deafult base/root url
   *  
   *  @example:
   *    // base url: http://app.com/
   *    // current url: http://app.com/bar/foo/?che=baz   
   *    $this->get(); // -> http://app.com/
   *    $this->get('bar','foo'); // -> http://app.com/bar/foo
   *    $this->get('bar',array('foo' => 'baz')); // -> http://app.com/bar/?foo=baz
   *    $this->getCurrent(); // -> http://app.com/bar/foo/?che=baz
   *    $this->get('http://example.com','bar','foo'); // -> http://example.com/bar/foo   
   *            
   *  @param  mixed $bit,...  first argument can be a root. Last argument can be
   *                          an array of key/val mappings that will be turned into
   *                          a query string, all other args will be treated as path components
   *  @return string  a complete url with the passed in args used to build it
   */
  public function get($bit = ''){
  
    $args = func_get_args();
    $url_map = $this->normalize($args);
    
    return $this->build($url_map['url'],$url_map['path'],$url_map['query']);
  
  }//method
  
  /**
   *  same as {@link getCurrent()} but it strips out any get vars that would have been included
   *  with the question mark. this is handy when you want to use the current url but you
   *  don't want the query vars that are already on the current url
   *  
   *  @example
   *    $url->getCurrent(); // -> http://example.com/foo/?bar=che
   *    $url->getPath(); // -> http://example.com/foo/
   *    $url->getPath(array('che' => 'bar')); // -> http://example.com/foo/?che=bar   
   *  
   *  @see  get()
   *  @return string  the base url with no ?key=val... string (unless one was passed in)   
   */
  public function getPath($bit = ''){
  
    $args = func_get_args();
    
    $url_map = $this->normalize($args);
    $current_url_map = $this->split($this->getCurrent());
    
    
    list($url,$current_query) = $this->split($this->getCurrent());
    
    return $this->build($base_url,$path_list,$field_map);
  
  }//method
  
  /**
   *  gets the current url, can take passed in values similar to {@link get()} but will always
   *  use the current url as a base
   *  
   *  @see  get()         
   *  @return string  the current url with any passed in vars appended to it
   */
  public function getCurrent($bit = ''){
  
    $args = func_get_args();
    // get the base url...
    $current_url = $this->getField('Url.current_url',null);
    if($current_url === null){
      $current_url = $this->getField('Url.base_url','');
    }//if
    
    $url_map = $this->normalize($args);
    return $this->build($current_url,$url_map['path'],$url_map['query']);
    
  }//method
  
  /**
   *  set a get field that will be included with every url built with this class
   *  using methods like {@link append()} and {@link get()}, etc.   
   *
   *  this is a handy way to set fields that will follow a visitor around as they
   *  navigate the site   
   *      
   *  @since  5-27-10
   *  @see  append()   
   *  
   *  @param  string  $key  the name of the get field
   *  @param  string  $val  the value for the get field      
   */
  public function setGlobalField($key,$val){
  
    // canary...
    if(empty($key)){
      throw new UnexpectedValueException('$key is empty');
    }//if
  
    $field_key = 'Url.get_field_map';
    $global_get_field_map = $this->getField($field_key,array());
  
    $global_get_field_map[$key] = $val;
  
    return $this->setField($field_key,$global_get_field_map);
  
  }//method
  
  /**
   *  adds query fields to the given url.
   *  
   *  this will url encode the query fields, it will also add any fields that were set
   *  with {@link setGlobalField()}
   *  
   *  @param  string  $url  the url to append the fields to
   *  @param  array $field_map the get fields that will be appended in key => val mappings
   *  @return string  the url with the vars attached
   */
  public function append($url,$field_map = array()){
    
    // sanity...
    if(empty($field_map)){ return $url; }//if
    
    list($url,$query_field_map) = $this->split($url);
    
    // build a definitive field map that can be appended...
    $field_map = array_merge(
      $this->getField('Url.get_field_map',array()),
      $query_field_map,
      $field_map
    );
    
    if(!empty($field_map)){
    
      // collect all the fields that don't have a value (so we can do ?val& instead of ?val=&)
      $single_field_map = array();
      foreach($field_map as $key => $val){
      
        if(($val === null) || ($val === '')){
          $single_field_map[] = urlencode($key);
          unset($field_map[$key]);
        }//if
      
      }//foreach
    
      $field_str = http_build_query($field_map,'','&');
      $single_field_str = empty($single_field_map) ? '' : join('&',$single_field_map);
      if(empty($field_str)){
        $field_str = $single_field_str; 
      }else{
        if(!empty($single_field_str)){
          $field_str = sprintf('%s&%s',$field_str,$single_field_str);
        }//if
      }//if/else
      
      if(!empty($field_str)){ $url = sprintf('%s?%s',$url,$field_str); }//if
    
    }//if
    
    return $url;
    
  }//method
  
  /**
   *  remove select parts of a url
   *  
   *  this method follows the same passed in arg form as {@link get()} and others, 
   *  the url to strip will be the first argument, the path pieces to be removed will
   *  be any non-array strings following the url, and if that last passed in arg is an
   *  array that will be the get values to be removed         
   *      
   *  this method is kind of hard to describe so examples might be best:
   *  
   *  @since  5-26-10
   *      
   *  @example
   *    $this->kill(
   *      'http://app.com/foo/bar/?che=baz&dah', // full url to remove stuff from
   *      'bar', // strip 'bar' from the url                     
   *      array('che' => null) // remove 'che' query field if found (value doesn't matter)
   *    ); // -> 'http://app.com/foo/?dah'
   *    
   *    $this->kill(
   *      'http://app.com/foo/bar/?che=baz&dah', // full url to remove stuff from
   *      'bar', // strip 'bar' from the url                     
   *      array('che' => 'baz') // remove 'che' query field if its value is 'baz'
   *    ); // -> 'http://app.com/foo/?dah'      
   *
   *  @param  mixed $arg,...  first argument must be a url, last argument can be
   *                          an array of key/val mappings that will be turned into
   *                          a query string, all other args will be treated as path bits
   *  @return string  a url with the found path bits and query vars removed
   */
  public function kill(){
  
    $args = func_get_args();
    // sort through the passed in args...
    list($url,$kill_path_list,$kill_field_map) = $this->normalize($args);
    // deconstruct the passed in url that will have stuff removed...
    list($base_url,$path_list,$field_map) = $this->unbuild($url);

    if(!empty($path_list)){
      
      // get rid of the path vars that match...
      foreach($kill_path_list as $val){
      
        $key = array_search($val,$path_list,true);
        if($key !== false){ unset($path_list[$key]); }//if
      
      }//foreach
      
    }//if
  
    if(!empty($field_map)){
    
      // get rid of the path vars that match...
      foreach($kill_field_map as $key => $val){
      
        // the var map might contain a null value? So let's not use isset just to be safe...
        if(array_key_exists($key,$field_map)){
          
          // if the value is null then just delete the key...
          if($val === null){
            
            unset($field_map[$key]);
            
          }else{
          
            // since the $val has a value, then only remove the field if it matches the value...
            if($val == $field_map[$key]){
              unset($field_map[$key]);
            }//if
          
          }//if/else
          
        }//if

      }//foreach
    
    }//if
    
    return $this->build($base_url,$path_list,$field_map);
  
  }//method
  
  /**
   *  deconstruct a string url to its parts
   *
   *  this method will deconstruct a url into the parts suitable to be passed to {@link build()}
   *  
   *  @since  5-26-10
   *      
   *  @param  string  $url
   *  @return array array($base_url,$path_list,$field_map)
   */
  public function unbuild($url){
  
    // canary...
    if(empty($url)){ throw new \InvalidArgumentException('$url was empty'); }//if
  
    $url_map = parse_url($url);
    
    $ret_base = '';
    if(!empty($url_map['host'])){
      $ret_base = sprintf(
        '%s://%s',
        empty($url_map['scheme']) ? $this->scheme : $url_map['scheme'],
        $url_map['host']
      );
    }//if
    
    $ret_path = array();
    if(!empty($url_map['path'])){
      $ret_path = array_filter(explode(self::URL_SEP,$url_map['path']));
    }//if
    
    $ret_field_map = array();
    if(!empty($url_map['query'])){
      parse_str($url_map['query'],$ret_field_map);
    }//if
  
    if(!empty($url_map['fragment'])){
      $ret_path[] = $url_map['fragment'];
    }//if
    
    return array($ret_base,$ret_path,$ret_field_map);
  
  }//method
  
  /**
   *  split a string into base and get field_map
   *  
   *  @param  string  $url
   *  @return array array($url,$field_map) where $url is the url without a ?... string, and $field_map
   *                is an array of the key/value pairs the $url originally contained
   */
  public function split($url){
    
    // canary...
    if(empty($url)){ return array('',array()); }//if
    
    $field_map = array();
    
    $query_str_start = mb_strpos($url,'?');
    if($query_str_start !== false){
    
      // the url does have a query string, so parse it and then merge it into the url...
      
      $url_query_str = mb_substr($url,$query_str_start + 1);
      $url = mb_substr($url,0,$query_str_start); // we just want the regular url
      
      parse_str($url_query_str,$field_map);
      $field_map = $field_map; // these might have slashes if magic quotes is on
      
    }//if
  
    return array($url,$field_map);
    
  }//method
  
  /**
   *  returns true if the current url is the same as the passed in url, false otherwise...
   *  
   *  @param  string  $url  the url to check against the current url
   *  @param  boolean $path_only  if true, then the query string will be stripped off the current
   *                              url, this is handy if you are doing tabs or something            
   *  @return string
   */
  public function isSame($url,$path_only = false){
  
    // sanity...
    if(empty($url)){ return false; }//if
  
    $current_url = $path_only
      ? $this->getPath()
      : $this->getCurrent();
  
    // normalize the urls...
    $url = rtrim(mb_strtoupper($url),self::URL_SEP);
    $current_url = rtrim(mb_strtoupper($current_url),self::URL_SEP);
    
    return ($current_url === $url);
  
  }//method
  
  /**
   *  magically allow this class set fields using the method call
   *  
   *  @param  string  $method the method that was called
   *  @param  array $args the arguments passed into the $method call         
   *  @return mixed
   */
  public function __call($method,$args){
  
    $method_map = array(
      'set' => 'handleSet',
      'get' => 'handleGet',
      'has' => 'handleHas'
    );
    
    return $this->getCall($method_map,$method,$args);
  
  }//method
  
  /**
   *  true if $val is a url
   *  
   *  @param  string  $val
   *  @return true if $val is a url, false otherwise
   */
  public function isUrl($val){
  
    // canary...
    if(empty($val)){ return false; }//if
    if(!is_string($val)){ return false; }//i

    return preg_match('#^\w+://\S{3,}#',$val) ? true : false;
    
  }//method
  
  /**
   *  true if a given url base has been defined
   *  
   *  this method is called from {@link __call()}      
   *  
   *  @example
   *    // we want to set a default foo base...
   *    $this->setFoo(self::SCHEME_NORMAL,'app.com','foo');
   *    $this->getFoo(); // -> http://app.com/foo
   *    $this->hasFoo(); // -> true                    
   *
   *  @param  string  $field  the field to see if it exists
   *  @param  array $args ignored by this function, but passed in by default
   *  @return boolean   
   */
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
   *    $this->setFoo(self::SCHEME_NORMAL,'app.com','bar');
   *    $this->getFoo(); // -> http://app.com/bar       
   *
   *    // even easier way to make foo point to bar...
   *    // base url: http://app.com   
   *    $this->setFoo('bar');
   *    $this->getFoo(); // -> http://app.com/bar      
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
    if(empty($args)){
      throw new RuntimeException(
        join(
          "\r\n",
          array(
            'Cannot set with an empty $args array. Any set* methods can take up to 3 arguments: ',
            ' - 1 argument: [path (eg, /foo/bar)]',
            ' - 2 arguments: [host (eg, example.com), path]',
            ' - 3 arguments: [scheme (eg, http), host, path].'
          )
        )
      );
    }//if 
  
    $total_args = count($args);
    
    if($total_args === 1){
    
      $base_url = $this->getField('Url.base_url','');
      $ret_str = $args[0];
      
      $this->setField($field,$this->assemble('',$base_url,$ret_str));
    
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
   *  most of the get methods can take a variable string of arguments, with an array at the
   *  end that will be turned into a get parameter string, this separates the path vars (eg, foo/bar)
   *  from the parameter fields (eg, ?foo=bar) and returns them
   *      
   *  @param  array $args
   *  @return array array has keys: url, path, and query. Where url is the url that will be at the beginning
   *                and $path is all the path elements (eg, array('foo','bar') to become: /foo/bar later)
   *                and query are the get vars (eg, array('foo' => 'bar') would become ?foo=bar later)      
   */
  protected function normalize(array $args){
  
    $ret_map = array(
      'url' => '',
      'path' => array(),
      'query' => array()
    );
  
    if(empty($args)){
    
      $ret_map['url'] = $this->getField('Url.base_url','');
      
    }else{
      
      $path_list = $field_map = array();
    
      // if the last element is an array, then it is a var_map, else it is just a normal value...
      $field_map = end($args);
      if(is_array($field_map)){
      
        // the last element isn't part of the url path list, it contains the get vars...
        $path_list = array_slice($args,0,-1);
        
      }else{
      
        $path_list = $args;
        $field_map = array();
        
      }//if/else
      
      $url = empty($path_list[0]) ? '' : $path_list[0];
      
      if($this->isUrl($url)){
      
        $path_list = array_slice($path_list,1);
      
      }else{
      
        $url = $this->getField('Url.base_url','');
      
      }//if/else
      
      $ret_map['url'] = $url;
      $ret_map['path'] = $path_list;
      $ret_map['query'] = $field_map;
      
    }//if/else
    
    return $ret_map;
  
  }//method
  
  /**
   *  given a $scheme, $base, $path or combination of the 3 build a url
   *  
   *  @param  string  $scheme can be one of the SCHEME_* constants
   *  @param  string  $$base  usually something like example.com
   *  @param  string  $path something like /foo/bar
   *  @return string  all the 3 parts combined
   */
  protected function assemble($scheme,$base,$path = ''){
    
    if(empty($base)){
    
      $base = $this->getField('Url.base_url','');
    
    }//if/else
    
    $url_bits = empty($base) ? array() : parse_url($base);

    if($this->isUrl($base)){
    
      if(empty($scheme)){
      
        $scheme = $url_bits['scheme'];
        $base = sprintf(
          '%s%s',
          isset($url_bits['host']) ? $url_bits['host'] : '',
          isset($url_bits['path']) ? rtrim($url_bits['path'],self::URL_SEP) : ''
        );
        
      }//if
    
    }else{
    
      // get rid of the :// because we are going to add it later and don't want to
      // add it twice...
      $scheme = empty($scheme) ? $this->scheme : rtrim($scheme,':/');
    
    }//if/else

    $list = array();
  
    if(!empty($base)){
      $base = rtrim($base,self::URL_SEP);
      $base = sprintf('%s://%s',$scheme,$base);
      $list[] = $base;
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
   *     
   *  build works by adding all arguments to the $url, if the last argument is 
   *  an array, those values will be added as get variables (ie, build('one','two',array('this' => 'that'))
   *  would turn into "[base]/one/two/?this=that), likewise, if the last non-array var has a # as the first
   *  char, it will become a fragment on the url before the get vars
   *  
   *  @since  9-6-08
   *      
   *  @param  string  $url  the base url to start from
   *  @param  array $path the vars to add to the base
   *  @param  array $query  the get vars to append to the end of the url build with $bars and $var_list   
   *  @return string  the completely built url
   */
  protected function build($url,array $path,array $query = array()){
    
    // sanity...
    if(empty($url)){ throw new \InvalidArgumentException('$url was empty'); }//if
    if(empty($path) && empty($query)){ return $url; }//if
    
    $ret_str = '';
    
    $base_bits = parse_url($url);
    
    $ret_str .= empty($base_bits['scheme']) ? '' : $base_bits['scheme'].'://';
    
    if(!empty($base_bits['user']) && !empty($base_bits['pass'])){
      $ret_str .= $base_bits['user'].':'.$base_bits['pass'].'@';
    }//if
    
    $ret_str .= empty($base_bits['host']) ? '' : $base_bits['host'];
    $ret_str .= empty($base_bits['port']) ? '' : ':'.$base_bits['port'];
    $ret_str .= empty($base_bits['path']) ? '' : $base_bits['path'];
    
    if(!empty($base_bits['query'])){
    
      $url_query = array();
      parse_str(htmlspecialchars_decode($base_bits['query']),$url_query);
    
      if(empty($url_query)){
      
        $url_query = $query;
      
      }else{
      
        // passed in $query dominates the original $url query fields...
        $query = array_merge($url_query,$query);
        
      }//if/else
    
    }//if
    
    $ret_str = rtrim($ret_str,self::URL_SEP);
    $check_slash = true;
    
    // add path bits to the end of the url if there are any...
    if(!empty($path)){
    
      // format and remove blank path bits...
      $path = array_map(array($this,'format'),$path);
      $path = array_filter($path);
    
      // see if we have a fragment at the end of the path...
      $last = end($path);
      if($last[0] === '#'){
      
        $path = array_slice($path,0,-1); // remove the fragment
        $base_bits['fragment'] = $last; // takes precedense over $url fragment
        
      }//if
      
      if(!empty($path)){
      
        $ret_str .= self::URL_SEP.join(self::URL_SEP,$path);
        
        // see if we want to add a trailing slash...
        $last = end($path);
        $check_slash = (mb_strrpos($last,'.') === false);
        
      }//if/else
    
    }//if
    
    if($check_slash){
    
      if($this->getField('trailing_slash',true)){ $ret_str .= self::URL_SEP; }//if
    
    }//if
    
    // add any get vars to the url...
    $ret_str = $this->append($ret_str,$query);
    
    // fragment should always be at the end...
    $ret_str .= empty($base_bits['fragment']) ? '' : $base_bits['fragment'];
    
    return $ret_str;
    
  }//method
  
  protected function format($val){
    
    // canary...
    if(ctype_space($val)){ return ''; }//if
    if(empty($val)){ return ''; }//if
    
    $ret_str = is_object($val) ? get_class($val) : $val;
    $ret_str = $this->trimSlash($val);
    return $ret_str;
    
  }//method
  
  /**
   *  trim a slash / off the end of $val
   *  
   *  @since  7-7-11
   *  @return string
   */
  protected function trimSlash($val,$both_sides = true){
  
    if($both_sides){
  
      $val = trim($val,self::URL_SEP);
      
    }else{
    
      $val = rtrim($val,self::URL_SEP);
    
    }//if/else
  
    return $val;
  
  }//method

}//class     
