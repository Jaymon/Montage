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
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-27-11
 *  @package montage
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Montage\Autoload;

use Montage\Autoload\Autoloader;
use Montage\Path;
use Montage\Cache\Cacheable;

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
    $ret_bool = $this->handleCache($file_name);
    
    // check instance paths...
    if($ret_bool === false){
    
      $ret_bool = $this->handlePaths($file_name);
    
    }//if
    
    if($ret_bool === false){
    
      // check include paths...
      $ret_bool = $this->handleIncludePaths($file_name);
      
    }//if
  
  }//method
  
  /**
   *  handle checking $file_name against all the default php include paths
   *  
   *  @since  9-1-11   
   *  @param  string  $file_name  the normalized class file name      
   *  @return boolean
   */ 
  protected function handleIncludePaths($file_name){
  
    $ret_bool = false;
  
    // check include paths...  
    foreach($this->getIncludePaths() as $path){
    
      if($classpath = $this->req($path,$file_name)){
      
        $this->class_map[$file_name] = $classpath;
        $ret_bool = true;
        break;
    
      }//if
    
    }//foreach
  
    return $ret_bool;
  
  }//method
  
  /**
   *  handle checking $file_name against all the paths defined with {@link addPath()}
   *  
   *  @since  9-1-11   
   *  @param  string  $file_name  the normalized class file name      
   *  @return boolean
   */
  protected function handlePaths($file_name){
  
    $ret_bool = false;
  
    // check user defined paths...
    foreach($this->getPaths() as $path){
    
      if($classpath = $this->req($path,$file_name)){
    
        $this->class_map[$file_name] = $classpath;
        $ret_bool = true;
        break;
      
      }//if
    
    }//foreach
    
    return $ret_bool;
  
  }//method
  
  /**
   *  handle checking $file_name against the internal object cache
   *  
   *  @since  9-1-11
   *  @param  string  $file_name  the normalized class file name      
   *  @return boolean
   */
  protected function handleCache($file_name){
  
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
   *  require the full path if it exists
   *     
   *  @since  7-22-11
   *  @param  string  $path the base path without a trailing slash
   *  @param  string  $file_name  the file that will be appended to the path      
   *  @return string  the path that was required
   */
  protected function req($path,$file_name){

    $file = $path.DIRECTORY_SEPARATOR.$file_name;

    if(is_file($file)){
      
      require($file);
      
    }else{
    
      $file = '';
    
    }//if/else
    
    return $file;

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
   *  set the object that will do the caching for any class that implements this interface
   *  
   *  @param  Montage\Cache $cache  the Cache instance
   */
  public function setCache(\Montage\Cache\Cache $cache = null){
    
    $this->cache = $cache;
    ///if($cache !== null){ $this->importCache(); }//if
    
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
  
    $this->exportCache();
  
  }//method

}//class     
