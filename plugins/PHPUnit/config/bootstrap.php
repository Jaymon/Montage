<?php
/**
 *  this is the bootstrap file for phpunit, since it relies on relative paths, YOU
 *  CANNOT MOVE IT without stuff breaking, you have been warned 
 *  
 *  there are potential problems with this file. First, a developer might have overridden
 *  Montage\Framework and so it would need to be changed. You could actually get around that
 *  by just having a bootstrap template that gets written to the cache directory with
 *  values like what Montage\Framework was used and the app_path to use.    
 *  
 *  @package    test
 *  @subpackage PHPUnit
 *  @author     Jay Marcyes
 ******************************************************************************/

// include the framework...
include_once(realpath(__DIR__.'/../../../src/Montage/Framework.php'));

// all php's error stuff should be on...
///error_reporting(-1);
///ini_set('display_errors','on');

$app_path = '';

// there has to be a better more Montage way to do this, but I can't think of anything right now...
if(!empty($_SERVER['argv'])){

  foreach($_SERVER['argv'] as $arg){
  
    $matched = array();
  
    if(preg_match('#--app-path=(\S+)#i',$arg,$matched)){
    
      $app_path = $matched[1];///trim($matched[1],'"\'');

    }//if
    
  }//foreach
  
}//if

// create and activate the framework...
$framework = new \Montage\Framework('test',1,$app_path);
$framework->activate();

// hack to get out class to load if it is available... 
class_exists('out');

// set the static instance that any children can use...
\PHPUnit\FrameworkTestCase::setFramework($framework);

// get rid of any variables so PHPUnit doesn't try to save them and add them to $GLOBALS... 
unset($framework);
unset($app_path);
