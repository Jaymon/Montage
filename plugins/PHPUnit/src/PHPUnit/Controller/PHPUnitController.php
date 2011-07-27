<?php

namespace PHPUnit\Controller;

use Montage\Controller\Controller;
use Montage\Config\FrameworkConfig;
use Montage\Request\Request;
use Montage\Response\Response;
use Montage\Path;

class PHPUnitController extends Controller {

  protected $framework_config = null;

  public function __construct(FrameworkConfig $framework_config){
  
    $this->framework_config = $framework_config;
  
  }//method

  public function preHandle(){
  
    if(!$this->request->isCli()){
    
      throw new \RuntimeException('Only command line requests are allowed for PHPUnit');
    
    }//if
  
  }//method

  public function handleIndex(array $params = array()){
  
    
  
  
  
  }//method
  

  public function handleTest(array $test_list = array()){
  
    \out::e($test_list);
  
    // find and add all the passed in test paths...
    foreach($test_list as $test_name)
    {
      $test_name = $this->normalizeTestName($test_name);
    
      
    
    
      $test_path = '';
    
      if(is_file($test_name))
      {
        $test_path = $test_name;
      
        // if the test is a full path, add its directory as an include path also (just in case)...
        $include_path_list[] = dirname($test_name);
        
      }
      else
      {
        $test_path = $this->findTest($test_name);
        
      }//if/else
    
      if(empty($test_path))
      {
        echo sprintf('could not find a valid test path for test "%s"',$test_name),PHP_EOL;
      }
      else
      {
        $tests[] = $test_path;
      }//if
    
    }//foreach
    
    return false;
  
  }//method
  
  /**
   *  Converts something like 'User' to 'UserTest.php'
   *  
   *  @param  string  $test_name
   *  @return string  the $test_name standardized
   */
  protected function normalizeTestName($test_name){
  
    // make sure it ends with .php...
    if(!preg_match('#\.php$#i',$test_name))
    {
      // add Test before the .php if it isn't there...
      if(!preg_match('#Test$#i',$test_name))
      {
        $test_name .= 'Test';
      }//if
      
      // final name should be <NAME>Test.php
      $test_name .= '.php';
      
    }//if
  
    return $test_name;
  
  }//method
  
  protected function normalizeTestPath($test_name){
  
    $test_name = $this->normalizeTestName($test_name);
  
  
  }//method
  
  protected function findTest($test_name)
  {
    // canary...
    if(empty($test_name))
    {
      throw new \UnexpectedValueException('$test_name was empty');
    }//if
  
    $ret_path = '';
  
    // first, check the main test dir...
    $test_dir = $this->getMainTestDir();
    $test_path = $this->getPath($test_dir,$test_name);
    if(is_file($test_path))
    {
      $ret_path = $test_path;
    }
    else
    {
    
      $test_dirs = $this->getSubTestDirs();
      foreach($test_dirs as $test_dir)
      {
        $test_path = $this->getPath($test_dir,$test_name);
        if(is_file($test_path))
        {
          $ret_path = $test_path;
          break;
        }//if
      
      }//foreach
    
    }//if
  
    return $ret_path;
  
  }//method
  
  /**
   *  returns the App's primary test dir
   *  
   *  this is the applications root testing dir/phpunit
   * 
   *  @since  2-26-11
   *  @return Montage\Path
   */
  protected function getAppTestDir()
  {
    $app_dir = $this->framework_config->getAppPath();
    return new Path($app_dir,'test','PHPUnit');
  
  }//method
  
  protected function getSubTestDirs()
  {
    $ret_list = array();
  
    // get all the plugin test paths...
    $plugin_paths = $this->framework_config->getPluginPaths();
    foreach($plugin_paths as $plugin_path)
    {
      $plugin_test_path = $this->getPath($plugin_path,'test','phpunit');
      if(is_dir($plugin_test_path))
      {
        $ret_list[] = $plugin_test_path;
      }//if
    
    }//foeach
  
    return $ret_list;
  
  }//method

}//class
