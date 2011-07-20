<?php

/**
 *  handle generic autoloading
 *  
 *  implements the portable autoloader requirements:
 *  http://groups.google.com/group/php-standards/web/psr-0-final-proposal  
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-27-11
 *  @package montage
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Montage\AutoLoad;

use Montage\Autoload\AutoLoadable;
use Symfony\Component\ClassLoader\UniversalClassLoader;
use Montage\Path;

class StdAutoLoader extends UniversalClassLoader implements Autoloadable {

  public function addPaths(array $path_list){
  
    $namespace_fallback_list = $this->getNamespaceFallbacks();
    $namespace_fallback_list = array_merge($namespace_fallback_list,$path_list);
    $this->registerNamespaceFallbacks($namespace_fallback_list);
    
    $prefix_fallback_list = $this->getPrefixFallbacks();
    $prefix_fallback_list = array_merge($prefix_fallback_list,$path_list);
    $this->registerPrefixFallbacks($prefix_fallback_list);
  
  }//method

  public function addPath($path){
  
    $namespace_fallback_list = $this->getNamespaceFallbacks();
    $namespace_fallback_list[] = $path;
    $this->registerNamespaceFallbacks($namespace_fallback_list);
    
    $prefix_fallback_list = $this->getPrefixFallbacks();
    $prefix_fallback_list[] = $path;
    $this->registerPrefixFallbacks($prefix_fallback_list);
  
  }//method

  /**
   *  unregister this class as an autoloader
   */
  public function unregister(){ spl_autoload_unregister($this->getCallback()); }//method
  
  /**
   *  get the callback that will be used to handle autoloading
   *  
   *  @since  7-5-11
   *  @return callback
   */
  public function getCallback(){ return array($this,'loadClass'); }//method

  /**
   *  this is what will do the actual loading of each autoloader
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name){ return $this->loadClass($class_name); }//method
  
  public function findFile($class){
  
    \out::e($class);
    return parent::findFile($class);
  
  }//method

}//class     
