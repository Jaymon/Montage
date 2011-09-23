<?php

/**
 *  handle generic autoloading
 *  
 *  implements the portable autoloader requirements:
 *  http://groups.google.com/group/php-standards/web/psr-0-final-proposal  
 *  
 *  @todo I still want to add a namespace/folder map, so you could do something like:
 *  
 *  $this->addNamespace($namespace,$dir); 
 *  $this->addNamespace('\Foo\Bar','che/baz');
 *  
 *  then, when you tried to create a class with the \Foo\Bar namespace:
 *  
 *  $instance = new \Foo\Bar\Happy\Blah();
 *  
 *  it would end up trying to include:
 *  
 *  che/baz/Happy/Blah.php     
 *  
 *  I'm not sure that is needed anymore though because {@link handleScan()} does everything
 *  that would allow with no input, and it's only slower the first time since it caches the
 *  correct path    
 *  
 *  @version 0.4
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-27-11
 *  @package montage
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Montage\Autoload;

use Montage\Autoload\Autoloader;
use Montage\Path;
use Montage\Cache\Cacheable;
use Montage\Dependency\ReflectionFile;

class StdAutoloader extends Autoloader implements Cacheable {

  /**
   *  holds the cache object
   *  
   *  @var  Montage\Cache\Cache
   */
  protected $cache = null;

  /**
   *  holds the user submitted paths
   *  
   *  @var  array
   */
  protected $path_list = array();
  
  /**
   *  holds the found classes and the paths where they exist
   *  
   *  this is the array that is cached
   *      
   *  @since  9-1-11   
   *  @var  array
   */
  protected $class_map = array();
  
  protected $export_cache = false;
  
  /**
   *  get all the user submitted paths
   *   
   *  @since  7-22-11
   *  @return array a lit of paths   
   */
  public function getPaths(){ return $this->path_list; }//method

  /**
   *  add all paths in the list to the path list
   *  
   *  @param  array $path_list  a list of paths
   */
  public function addPaths(array $path_list){
  
    foreach($path_list as $path){ $this->addPath($path); }//foreach
  
  }//method

  /**
   *  add one path to the list of paths
   *
   *  @param  string  $path the path to add to the user defined paths
   */
  public function addPath($path){
  
    $path = new Path($path); // this normalizes the path
    $this->path_list[] = $path;
  
  }//method

  /**
   *  this is what will do the actual loading of each autoloader
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name){

    $file_name = $this->normalizeClassName($class_name);
    
    // first check cache...
    $ret_bool = $this->handleCache($class_name,$file_name);
    
    // cache failed, so try to find the class...
    if($ret_bool === false){
    
      $method_list = $this->getHandleMethods();
      
      foreach($method_list as $method){
      
        if($ret_bool = $this->{$method}($class_name,$file_name)){
        
          // cache here
          break;
        
        }//if
              
      }//foreach

    }//if
  
  }//method
  
  /**
   *  does a brute for search of all the internal file paths looking for the class
   *  
   *  this method is extremely slow and is a last resort      
   *  
   *  my original notes: another thing that could be done is fallback to just search for the classname
   *  (eg, calling filename()) and then getting and including any files that match just the class's
   *  short name. Then checking class_exists after each one, then if found, cache the result.
   *      
   *  @since  9-7-11
   *  @param  string  $class_name the original looked for class name   
   *  @param  string  $file_name  the normalized class file name      
   *  @return boolean
   */
  protected function handlePathScan($class_name,$file_name){
  
    return $this->scanReq($class_name,$file_name,$this->getPaths());
  
  }//method
  
  /**
   *  does a brute for search of all the include file paths looking for the class
   *  
   *  this method is extremely slow and is a last last resort      
   *  
   *  @since  9-20-11
   *  @param  string  $class_name the original looked for class name   
   *  @param  string  $file_name  the normalized class file name      
   *  @return boolean
   */
  protected function handleIncludePathScan($class_name,$file_name){
  
    return $this->scanReq($class_name,$file_name,$this->getIncludePaths());
  
  }//method
  
  /**
   *  handle checking $file_name against all the default php include paths
   *  
   *  @since  9-1-11
   *  @param  string  $class_name the original looked for class name   
   *  @param  string  $file_name  the normalized class file name      
   *  @return boolean
   */ 
  protected function handleIncludePaths($class_name,$file_name){
  
    $ret_bool = false;
  
    // check include paths...  
    foreach($this->getIncludePaths() as $path){
    
      $file_path = $this->normalizePath($path,$file_name);
    
      if($ret_bool = $this->req($class_name,$file_name,$file_path)){ break; }//if
    
    }//foreach
  
    return $ret_bool;
  
  }//method
  
  /**
   *  handle checking $file_name against all the paths defined with {@link addPath()}
   *  
   *  @since  9-1-11
   *  @param  string  $class_name the original looked for class name   
   *  @param  string  $file_name  the normalized class file name      
   *  @return boolean
   */
  protected function handlePaths($class_name,$file_name){
  
    $ret_bool = false;
  
    // check user defined paths...
    foreach($this->getPaths() as $path){
    
      if($ret_bool = $this->req($class_name,$file_name,$this->normalizePath($path,$file_name))){ break; }//if
    
    }//foreach
    
    return $ret_bool;
  
  }//method
  
  /**
   *  handle checking $file_name against the internal object cache
   *  
   *  @since  9-1-11
   *  @param  string  $class_name the original looked for class name
   *  @param  string  $file_name  the normalized class file name      
   *  @return boolean
   */
  protected function handleCache($class_name,$file_name){
  
    $ret_bool = false;
  
    if(isset($this->class_map[$file_name])){
    
      // include will return false and emit E_WARNING if the file doesn't exist, that's ok
      // because that will trigger the method to try and find the file again and update the
      // cache...
      if((include($this->class_map[$file_name])) !== false){
      
        $ret_bool = true;
      
      }else{
      
        unset($this->class_map[$file_name]);
      
      }//if/else
    
    }//if
  
    return $ret_bool;
  
  }//method

  /**
   *  assemble the path
   *     
   *  @since  9-7-11
   *  @param  string  $path the base path without a trailing slash
   *  @param  string  $file_name  the file that will be appended to the path      
   *  @return string  the full assembled path
   */
  protected function normalizePath($path,$file_name){
  
    return $path.DIRECTORY_SEPARATOR.$file_name;
  
  }//method

  /**
   *  require the full path if it exists
   *     
   *  @since  7-22-11
   *  @param  string  $class_name the class name
   *  @param  string  $file_name  the $class_name ran through {@link parent::normalizeClassName()}         
   *  @param  string  $file_path  the full file path of the file that defines the class in $class_name
   *  @return boolean true if file was required
   */
  protected function req($class_name,$file_name,$file_path){

    $ret_bool = false;

    if(is_file($file_path)){
      
      // set cache stuff...
      $this->class_map[$file_name] = $file_path;
      $this->export_cache = true;
      
      require($file_path);
      $ret_bool = true;
      
    }//if
    
    return $ret_bool;

  }//method
  
  /**
   *  check $class_name in all the $path_list
   *  
   *  @since  9-20-11
   *  @param  string  $class_name the original looked for class name   
   *  @param  string  $file_name  the normalized class file name
   *  @param  array $path_list  a list of paths
   *  @return boolean
   */
  protected function scanReq($class_name,$file_name,array $path_list){
  
    $ret_bool = false;
    $class_bits = explode('\\',$class_name);
    $short_name = end($class_bits); // eg, for \foo\bar\baz return just baz
    
    // search for ClassName*.php, so things like ClassName.class.php are also found...
    $regex = sprintf('#%s\S*?\.(?:php\d*|inc)#i',$short_name);
    foreach($path_list as $path){
    
      if(!($path instanceof Path)){ $path = new Path($path); }//if
    
      $iterator = $path->createIterator($regex);
      foreach($iterator as $file){
      
        $rfile = new ReflectionFile($file->getPathname());
        if($rfile->hasClass($class_name)){
        
          if($ret_bool = $this->req($class_name,$file_name,$file->getPathname())){
        
            break 2;
            
          }//if
        
        }//if
      
      }//foreach
    
    }//foreach
    
    return $ret_bool;
  
  }//method
  
  /**
   *  get all the global application defined included paths
   *  
   *  I originally cached this, but that didn't allow future additions to the include path after the
   *  cache was set, so now it gets exploded everytime since it is only called when everything else fails
   *  I figure this isn't a big deal      
   *      
   *  @return array all the included paths, in an array
   */
  protected function getIncludePaths(){
  
    return explode(PATH_SEPARATOR,get_include_path());
  
  }//method
  
  /**
   *  return the handle methods that will be used if {@link handleCache()} failed
   *  
   *  this is basically here to make it easier to be overridden
   *  
   *  @since  9-8-11
   *  @return array
   */
  protected function getHandleMethods(){
  
    return array(
      'handlePaths', // check set internal instance paths using standardized naming conventions
      'handleIncludePaths', // check set php include paths
      'handlePathScan', // do a brute-force scan of all internal instance paths looking for the class
      'handleIncludePathScan' // do a brute-force of include paths (super super slow)
    );
  
  }//method
  
  /**
   *  set the object that will do the caching for any class that implements this interface
   *  
   *  @param  Montage\Cache $cache  the Cache instance
   */
  public function setCache(\Montage\Cache\Cache $cache = null){
    
    $this->cache = $cache;
    
  }//method
  
  /**
   *  get the caching object
   *  
   *  @return Montage\Cache\Cache
   */
  public function getCache(){ return $this->cache; }//method

  /**
   *  get the name of the cache
   *
   *  @return string    
   */
  public function cacheName(){ return get_class($this); }//method
  
  /**
   *  get the name of the params that should be cached
   *
   *  @return array an array of the param names that should be cached    
   */
  public function cacheParams(){ return array('class_map'); }//method

  /**
   *  using the Cache instance from {@link getCache()} cache the params with names
   *  returned from {@link cacheParams()}   
   *
   *  @return boolean   
   */
  public function exportCache(){
  
    $cache = $this->getCache();
  
    // canary, if no cache then don't try and persist...
    if(empty($cache)){ return false; }//if
  
    return ($cache->set($this->cacheName(),$this->class_map) > 0) ? true : false;
    
  }//method
  
  /**
   *  import the cached params and re-populate the params of the object instance
   *  with the param values that were cached
   *  
   *  @return boolean      
   */
  public function importCache(){
  
    $cache = $this->getCache();
  
    // canary, if no cache then don't try and persist...
    if(empty($cache)){ return false; }//if

    if($cache_map = $cache->get($this->cacheName())){
    
      $this->class_map = $cache_map;
    
    }//if
  
    return true;
  
  }//method
  
  /**
   *  delete the stored cache
   *  
   *  @return boolean      
   */
  public function killCache(){
  
    $cache = $this->getCache();
    if(empty($cache)){ return false; }//if
  
    return $cache->kill($this->cacheName());
  
  }//method
  
  public function __destruct(){
  
    if($this->export_cache){ $this->exportCache(); }//if
  
  }//method

}//class     
