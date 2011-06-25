<?php

/**
 *  handle caching
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-22-10
 *  @package montage 
 ******************************************************************************/
namespace Montage\Cache;

use Montage\Path;

class Cache {

  const PREFIX = 'montage_';

  /**
   *  hold the path where stuff will be cached
   * 
   *  @see  setPath()    
   *  @var  \Montage\Path
   */
  protected $path = null;
  
  protected $prefix = 'montage_';
  
  protected $namespace = array();
  
  public function setPrefix($prefix){
    $this->prefix = $prefix;
    return $this;
  }//method

  public function setNamespace($namespace){
    $this->namespace = (array)$namespace;
    return $this;
  }//method

  /**
   *  set the cache path, make sure it's valid
   *  
   *  @param  string  $path
   *  @return self
   */
  public function setPath($path){
  
    // canary...
    if(!($path instanceof Path)){
      $path = new Path($path);
    }//if
  
    $path->assure();
    $this->namespace_map[(string)$path] = true;
    $this->path = $path;
    return $this;
  
  }//method

  /**
   *  save the cache
   *  
   *  @param  string  $key
   *  @param  mixed $val  whatever it is, it will be serialized and saved in a file
   *  @return integer how many bytes were written
   */
  public function set($key,$val){
    
    $path = $this->getPath($key);
    
    $bytes = file_put_contents(
      $path,
      serialize($val),
      LOCK_EX
    );
    
    return $bytes;
  
  }//method
  
  /**
   *  get the cached value, null/false if nothing was found
   *  
   *  @param  string  $key
   *  @return mixed the cached whatever (eg, array, object)
   */
  public function get($key){
  
    $path = $this->getPath($key);
    return $path->exists() ? unserialize(file_get_contents($path)) : null;
  
  }//method
  
  /**
   *  checks if the given $key is cached
   *  
   *  @param  string  $key
   *  @return boolean
   */
  public function has($key){
  
    $path = $this->getPath($key);
    return $path->exists();
  
  }//method
  
  /**
   *  generate a key for the cache
   *  
   *  @param  string  $val
   *  @return string
   */
  protected function getKey($val){
    
    // canary...
    if(empty($val)){
      throw new UnexpectedValueException('cannot generate a key for an empty $val');
    }//if
    
    return sprintf('%s%s',self::PREFIX,md5($val));
    
  }//method

  /**
   *  get the full file cache path
   *  
   *  @param  string|array  $key  if string, then just the filename, if array, then it will be
   *                              the path and the last element will be the filename
   *                              (eg, array('foo','bar') becomes: self::getPath()/foo/self::getKey(bar))      
   *  @return string  the full path
   */
  protected function getPath($key){
    // canary...
    if(empty($this->path)){
      throw new \RuntimeException(sprintf('cache path is not set, call %s::setPath()',__CLASS__));
    }//if
    
    $base_path = $this->path;
    
    if(is_array($key)){
      
      // everything except the last element is the namespace, merge it with the global namespace...
      $namespace = array_slice($key,0,-1);
      if(!empty($namespace)){
      
        $namespace = array_merge($this->namespace,$namespace);
        
      }//if
      
      // if the merged namespace exists, then append it onto the path...
      if(!empty($namespace)){
      
        $base_path = new Path($this->path,$namespace);
        $base_path->assure();
        
      }//if
      
      $key = end($key);
      
    }//if
    
    $key = $this->getKey($key);
    
    return new Path($base_path,$key);
    
  }//method
  
  /**
   *  delete the given key from the cache
   *  
   *  @since  9-04-08
   *  @param  string  $key
   *  @return boolean    
   */
  public function kill($key){
  
    $path = $this->getPath($key);
    return $path->kill();
  
  }//method

}//class     
