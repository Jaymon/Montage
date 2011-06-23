<?php
/**
 *  any class can implement this interface and then be able to cache itself
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
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
  public function setCache(\Montage\Cache\Cache $cache);
  
  /**
   *  get the caching object
   *  
   *  @return Montage\Cache
   */
  public function getCache();

  /**
   *  get the name of the params that should be cached
   *
   *  @return array an array of the param names that should be cached    
   */
  public function cacheParams();

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
