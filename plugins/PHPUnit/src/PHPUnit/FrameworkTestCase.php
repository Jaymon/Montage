<?php
/**
 *  if used with the bootstrap file in config/ then this will have a static
 *  framework instance set so you can get access to everything in the framework, otherwise
 *  you'll need to set the framework via the static {@link setFramework()}  
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 7-28-11
 *  @package test
 *  @subpackage PHPUnit
 ******************************************************************************/
namespace PHPUnit;

use PHPUnit\TestCase;
use Montage\Framework;
use PHPUnit\FrameworkBrowser;

abstract class FrameworkTestCase extends TestCase {
  
  /**
   *  hold a copyt of the Framework
   *  
   *  @var  \Montage\Framework
   */
  static protected $framework = null;
  
  /**
   *  set the framework instance
   *  
   *  @param  \Montage\Framework  $framework
   */
  public static function setFramework(Framework $framework){
    self::$framework = $framework;
  }//method
  
  /**
   *  get this class's Framework instance
   *  
   *  @return \Montage\Framework
   */
  public function getFramework(){ return self::$framework; }//method

  /**
   *  get a client that is capable of making web requests for functional tests
   *
   *  @link http://symfony.com/doc/current/book/testing.html
   *  @return \PHPUnit\FrameworkClient
   */
  public function getClient(){
  
    $framework = clone $this->getFramework(); // use a copy of the already running framework
    return new FrameworkClient($framework);
  
  }//method

}//class
