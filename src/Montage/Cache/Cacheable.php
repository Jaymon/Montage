<?php
/**
 *  any class can implement this interface and then be able to cache itself
 *  
 *  @version 0.2
 *  @author Jay Marcyes
 *  @since 6-22-11
 *  @package montage 
 ******************************************************************************/
namespace Montage\Cache;

interface Cacheable {

  /**
   *  set the object that will do the caching for any class that implements this interface
   *  
   *  @param  Montage\Cache\Cache $cache  the Cache instance
   */
  public function setCache(\Montage\Cache\Cache $cache = null);
  
  /**
   *  get the caching object
   *  
   *  @return Montage\Cache
   */
  public function getCache();

  /**
   *  get the name of the cache
   *
   *  @return string    
   */
  public function cacheName();

  /**
   *  using the Cache instance from {@link getCache()} cache the params with names
   *  returned from {@link cacheParams()}   
   *
   *  @return boolean   
   */
  public function exportCache();
  
  /**
   *  import the cached params and re-populate the params of the object instance
   *  with the param values that were cached
   *  
   *  @return boolean      
   */
  public function importCache();
  
}//interface
