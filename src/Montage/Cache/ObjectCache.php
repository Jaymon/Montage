<?php
/**
 *  any class can extend this class and instantly have the ability to cache itself
 *  
 *  @version 0.3
 *  @author Jay Marcyes
 *  @since 6-22-11
 *  @package montage 
 ******************************************************************************/
namespace Montage\Cache;

use Montage\Cache\Cacheable;

class ObjectCache implements Cacheable {

  /**
   *  holds the cache object
   *  
   *  @var  Montage\Cache\Cache
   */
  protected $cache = null;
  
  /**
   *  set to true to have {@link __destruct()} export the cache
   *
   *  @var  boolean
   */
  protected $export_cache = false;

  /**
   *  set the object that will do the caching for any class that implements this interface
   *  
   *  @param  Montage\Cache $cache  the Cache instance
   */
  public function setCache(\Montage\Cache\Cache $cache = null){ $this->cache = $cache; }//method
  
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
   *  this was removed from the interface because it isn't really required to make
   *  the interface work, it's just a great helper method to make this base class
   *  a little easier to integrate, but other classes that are just going to implement
   *  the interface don't really need it since they have to define exportCache anyway         
   *      
   *  @return array an array of the param names that should be cached    
   */
  public function __sleep(){ return array_keys(get_object_vars($this)); }//method


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
  
    $param_name_list = $this->__sleep();
    
    $cache_map = array();
    foreach($param_name_list as $param_name){
    
      if(isset($this->{$param_name})){
    
        $cache_map[$param_name] = $this->{$param_name};
    
      }//if
    
    }//foreach
    
    return ($cache->set($this->cacheName(),$cache_map) > 0) ? true : false;
    
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

    $param_list = $cache->get($this->cacheName());
    if(!empty($param_list)){
      
      foreach($param_list as $param_name => $param_val){
      
        $this->{$param_name} = $param_val;
      
      }//foreach
      
    }//if
  
    return true;
  
  }//method
  
  public function __destruct(){
  
    if($this->export_cache){
    
      try{
    
        $this->exportCache();
      
      }catch(\Exception $e){
      
        $msg = sprintf(
          '"%s" in __destruct() might lead to a '
          .'"Fatal error: Exception thrown without a stack frame in Unknown on line 0',
          $e->getMessage()
        );
      
        trigger_error($msg,E_USER_NOTICE);
      
        throw $e;
      
      }//try/catch
      
    }//if
  
  }//method
  
}//interface
