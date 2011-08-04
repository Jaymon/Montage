<?php
/**
 *  if used with the bootstrap file in config/ then this will have a static
 *  framework instance set so you can get access to everything in the framework 
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
  
  static protected $framework = null;
  
  public static function setFramework(Framework $framework){
    self::$framework = $framework;
  }//method
  
  public function getFramework(){ return self::$framework; }//method

  public function getBrowser(){
  
    return new FrameworkBrowser($this->getFramework());
  
  }//method

}//class
