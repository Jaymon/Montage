<?php

/**
 *  handle caching
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-22-10
 *  @package montage 
 ******************************************************************************/
final class montage_cache {

  /**
   *  hold the path where stuff will be cached
   * 
   *  @see  setPath()    
   *  @var  string
   */
  static private $path = '';

  /**
   *  set the cache path, make sure it's valid
   *  
   *  @param  string  $path
   */
  static function setPath($path){
  
    // make sure path isn't empty...
    if(empty($path)){
      throw new UnexpectedValueException('cannot cache to an empty $path');
    }//if
    
    // make sure path is directory, try to create it if it isn't...
    if(!is_dir($path)){
      if(!mkdir($path,0755,true)){
        throw new UnexpectedValueException(
          sprintf('"%s" is not a valid directory and the attempt to create it failed.',$path)
        );
      }//if
    }//if
  
    // make sure the path is writable...
    if(!is_writable($path)){
      throw new RuntimeException(sprintf('cannot write to $path (%s)',$path));
    }//if
      
    // make sure path doesn't end with a slash...
    if(mb_substr($path,-1) == DIRECTORY_SEPARATOR){
      $path = mb_substr($path,0,-1);
    }//if
  
    self::$path = $path;
    return $path;
  
  }//method

  /**
   *  save the cache
   *  
   *  @param  string  $key
   *  @param  mixed $val  whatever it is, it will be serialized and saved in a file
   *  @return boolean true if saved, false otherwise
   */
  static function set($key,$val){
  
    // canary...
    $key = self::getKey($key);
    
    $bytes = file_put_contents(
      self::getPath($key),
      serialize($val),
      LOCK_EX
    );
    
    return empty($bytes) ? false : true;
  
  }//method
  
  /**
   *  get the cached value, null/false if nothing was found
   *  
   *  @param  string  $key
   *  @return mixed the cached whatever (eg, array, object)
   */
  static function get($key){
  
    $path = self::getPath($key);
    if(!self::exists($path)){ return null; }//if
    return unserialize(file_get_contents($path));
  
  }//method
  
  /**
   *  checks if the given $key is cached
   *  
   *  @param  string  $key
   *  @return boolean
   */
  static function has($key){
  
    $path = self::getPath($key);
    return self::exists($path);
  
  }//method
  
  /**
   *  delete the given url from the cache
   *  
   *  @since  9-04-08
   *      
   *  @param  string  $url  the url to delete
   *  @return boolean         
   */        
  static function kill($key = ''){
  
    $ret_bool = false;
  
    if(empty($key)){
    
      if(self::hasPath()){
      
        $ret_bool = self::killAll(self::$path);
      
      }//method
    
    }else{
    
      $path = self::getPath($key);
      if(self::exists($path)){
        $ret_bool = unlink($path);
      }//if
    
    }//if/else
  
    return $ret_bool;
  
  }//method
  
  /**
   *  generate a key for the cache
   *  
   *  @param  string  $val
   *  @return string
   */
  static private function getKey($val){
    
    // canary...
    if(empty($val)){
      throw new UnexpectedValueException('cannot generate a key for an empty $val');
    }//if
    
    return md5($val);
    
  }//method
  
  /**
   *  this is the private version of {@link has()} that doesn't need to render the path
   *  so it can be called from other methods that need the path and don't want to have
   *  to render it twice
   *  
   *  @param  string  $path
   *  @return boolean true if path is a file, false otherwise
   */
  static private function exists($path){ return is_file($path); }//method

  /**
   *  get the full file cache path
   *  
   *  @param  string  $key
   *  @return string  the full path
   */
  static private function getPath($key){
    // canary...
    if(empty(self::$path)){
      throw new RuntimeException(sprintf('cache path is not set, call %s::setPath()',__CLASS__));
    }//if
    $key = self::getKey($key);
    return join(DIRECTORY_SEPARATOR,array(self::$path,$key));
  }//method
  
  /**
   *  true if a path exists
   *  
   *  @param  string  $key
   *  @return string  the full path
   */
  static private function hasPath(){ return !empty(self::$path); }//method
  
  /**
   *  recursively clear an entire directory, files, folders, everything
   *  
   *  based off of: http://www.php.net/manual/en/function.unlink.php#94766      
   *
   *  @param  string  $path the starting path, all sub things will be removed
   */
  static private function killAll($path){
  
    // canary...
    if(!is_dir($path)){ return false; }//if
    
    $ret_bool = false;
    $path_iterator = new RecursiveDirectoryIterator($path);
    foreach($path_iterator as $file){
      
      $file_path = $file->getRealPath();
      
      if($file->isDir()){
      
        $ret_bool = self::killAll($file_path);
        rmdir($file_path);
      
      }else{
      
        unlink($file_path);
      
      }//if/else
      
    }//foreach
    
    // uncomment if you want to clear the root dir, which you don't...
    ///rmdir($path);
    
    return $ret_bool;
    
  }//method

}//class     
