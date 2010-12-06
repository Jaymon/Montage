<?php

error_reporting(E_ALL | E_STRICT | E_PARSE);
ini_set('display_errors','on');

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

class montage_test_base extends PHPUnit_Framework_TestCase {

  /**
   *  returns the absolute base of where all the fixtures live
   *   
   *  @return string
   */
  protected function getFixtureBase(){
  
    return join(DIRECTORY_SEPARATOR,array($this->getTestBase(),'fixtures'));
  
  }//method
  
  /**
   *  returns the base of where this test's fixtures live
   *   
   *  @return string
   */
  protected function getClassFixtureBase(){
  
    $rclass = new ReflectionClass(get_class($this));
    $filename = $rclass->getFileName();
    $dir = dirname($filename);
    $base = dirname(__FILE__);
    $diff = str_replace($base,'',$dir);
    return sprintf('%s%s',$this->getFixtureBase(),$diff);
  
  }//method
  
  /**
   *  returns the test base directory
   *   
   *  @return string
   */
  protected function getTestBase(){
    return realpath(join(DIRECTORY_SEPARATOR,array(dirname(__FILE__),'..')));
  }//method

}//class
