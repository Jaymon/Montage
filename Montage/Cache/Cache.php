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


  /**
   *  hold the path where stuff will be cached
   * 
   *  @see  setPath()    
   *  @var  \Montage\Path
   */
  protected $path = null;
  
  /**
   *  the prefix that will be used for any cache created by this class
   *
   *  @var  string   
   */
  protected $prefix = 'montage_';
  
  protected $namespace = array();
  
  public function setPrefix($prefix){
    $this->prefix = $prefix;
    return $this;
  }//method

  /**
   *  namespace is used to globally set a folder structure that everything cached
   *  will automatically go into   
   *
   *  @param  string|array  $namespace  one or more folders (eg setNamespace('foo') or setNamespace(array('foo','bar'))   
   *  @return this
   */
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
    if(!($path instanceof Path)){ $path = new Path($path); }//if
  
    $path->assure();
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
  
  /**
   *  clear all the cache
   *  
   *  @since  7-6-11
   *  @return integer cleared cache count
   */
  public function clear(){
  
    $path = $this->getPath('',false);
    return $path->kill();
  
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
      throw new \UnexpectedValueException('cannot generate a key for an empty $val');
    }//if
    
    return $this->prefix.md5($val);
    
  }//method

  /**
   *  get the full file cache path
   *  
   *  @param  string|array  $key  if string, then just the filename, if array, then it will be
   *                              the path and the last element will be the filename
   *                              (eg, array('foo','bar') becomes: self::getPath()/foo/self::getKey(bar))      
   *  @return string  the full path
   */
  protected function getPath($key = '',$assure_key = true){
    // canary...
    if(empty($this->path)){
      throw new \RuntimeException(sprintf('cache path is not set, call %s::setPath()',__CLASS__));
    }//if
    if($assure_key && empty($key)){
      throw new \UnexpectedValueException('$key was empty');
    }//if
    
    $namespace = array();
    
    if(is_array($key)){
      
      // everything except the last element is the namespace, merge it with the global namespace...
      $namespace = array_slice($key,0,-1);
      $key = end($key);
      
    }//if
    
    $path = new Path($this->path,$this->namespace,$namespace);
    $path->assure();
    
    if(!empty($key)){
      
      $key = $this->getKey($key);
      $path = new Path($path,$key);
      
    }//if
    
    return $path;
    
  }//method

}//class     
