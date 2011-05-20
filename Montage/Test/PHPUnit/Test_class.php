<?php
namespace Montage\Test\PHPUnit;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

error_reporting(-1);
ini_set('display_errors','on');

/*
// declare a simple autoloader we can use...
include_once(
  join(
    DIRECTORY_SEPARATOR,
    array(
      dirname(__FILE__),
      'montage_test_autoload_class.php'
    )
  )
);
montage_test_autoload::register();
*/

abstract class Test extends PHPUnit_Framework_TestCase {

  /**
   *  returns the absolute base of where all the fixtures live
   *   
   *  @param  string  $args,... one or more bits of a path   
   *  @return string
   */
  protected function getFixture(){
  
    $path_bits = func_get_args();
    return join(DIRECTORY_SEPARATOR,array_merge(array($this->getTestBase(),'Fixtures'),$path_bits));
  
  }//method
  
  /**
   *  returns the base of where this test's fixtures live
   *   
   *  @return string
   */
  /* protected function getClassFixtureBase(){
  
    \out::e(get_class($this));
  
    $rclass = new ReflectionClass(get_class($this));
    $filename = $rclass->getFileName();
    $dir = dirname($filename);
    $base = dirname(__FILE__);
    $diff = str_replace($base,'',$dir);
    $ret_str = sprintf('%s%s',$this->getFixtureBase(),$diff);
    \out::e($ret_str);
    return $ret_str;
  
  }//method */
  
  /**
   *  returns the test base directory
   *   
   *  @return string
   */
  protected function getTestBase(){
    return realpath(join(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'..')));
  }//method

}//class
