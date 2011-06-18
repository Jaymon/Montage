<?php

/**
 *  handle caching
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-22-10
 *  @package montage 
 ******************************************************************************/
namespace Montage;

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
  
  protected $namespace_map = array();

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
    return unserialize(file_get_contents($path));
  
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
      
      // everything except the last element is the namespace...
      $namespace = array_slice($key,0,-1);
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
   *  recursively clear an entire directory, files, folders, everything
   *  
   *  based off of: http://www.php.net/manual/en/function.unlink.php#94766      
   *
   *  @param  string  $path the starting path, all sub things will be removed
   */
  protected function clear($path){
  
    throw new \Exception('tbi');
  
    // canary...
    if(!is_dir($path)){ return false; }//if
    
    $ret_bool = true;
    $path_iterator = new RecursiveDirectoryIterator($path);
    foreach($path_iterator as $file){
      
      $file_path = $file->getRealPath();
      
      if($file->isDir()){
        
        $ret_bool = self::clear($file_path);
        if($ret_bool){
          rmdir($file_path);
        }//if
      
      }else{
    
        // make sure we only kill files that are montage cache files since we don't
        // want to accidently nuke an app's personal cache...
        if(preg_match(sprintf('#^%s#u',self::PREFIX),$file->getFilename())){
      
          unlink($file_path);
          
        }else{
          $ret_bool = false;
        }//if/else
      
      }//if/else

    }//foreach
    
    return $ret_bool;
    
  }//method
  
  /**
   *  delete the given url from the cache
   *  
   *  @since  9-04-08
   *      
   *  @param  string  $url  the url to delete
   *  @return boolean         
   */        
  public function kill($key = ''){
  
    throw new \Exception('tbi');
  
    $ret_bool = false;
  
    if(empty($key)){
    
      if(self::hasPath()){
      
        $ret_bool = self::clear(self::$path);
      
      }//method
    
    }else{
    
      $path = self::getPath($key);
      if(self::exists($path)){
        $ret_bool = unlink($path);
      }//if
    
    }//if/else
  
    return $ret_bool;
  
  }//method

}//class     
