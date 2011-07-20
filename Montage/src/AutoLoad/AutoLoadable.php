<?php
/**
 *  any class can implement this interface and then be registered as autoloader
 *  
 *  if you just get blank pages when autoloading a class, maybe you used the @ symbol: 
 *    http://www.php.net/manual/en/function.error-reporting.php#28181
 *    http://www.php.net/manual/en/function.include-once.php#53239
 *   
 *  @link http://devzone.zend.com/article/4525 
 *  @link http://php.net/manual/en/language.oop5.autoload.php
 *  @link http://groups.google.com/group/php-standards/web/psr-0-final-proposal   
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-27-11
 *  @package montage
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Montage\AutoLoad;

interface Autoloadable {

  /**
   *  this is what will do the actual loading of each autoloader
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name);
  
  /**
   *  register this class as an autoloader
   *  
   *  @param  boolean $prepend      
   */
  public function register($prepend = false);
  
  /**
   *  get the callback that will be used to handle autoloading
   *  
   *  this will usually just return array($this,'handle');      
   *  
   *  @since  7-5-11
   *  @return callback
   */
  public function getCallback();
  
  /**
   *  unregister this class as an autoloader
   */
  public function unregister();
  
}//interface
