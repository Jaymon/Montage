<?php

namespace PHPUnit\Controller;

use Montage\Controller\CliController;
use Montage\Config\FrameworkConfig;
use Montage\Path;

class PHPUnitController extends CliController {

  protected $framework_config = null;

  public function __construct(FrameworkConfig $framework_config){
  
    $this->framework_config = $framework_config;
    
    
  
  }//method
  
  ///public function preHandle(){ parent::preHandle(); }//method

  public function handleIndex(array $params = array()){
  
    
  
  
  
  }//method
  

  public function handleTest(array $test_list = array()){
  
    $test_path_list = array();
  
    // find and add all the passed in test paths...
    foreach($test_list as $test_name)
    {
      $test_path = '';
    
      if(is_file($test_name))
      {
        $test_path = $test_name;
        
      }
      else
      {
        $test_path = $this->findTest($test_name);
        
      }//if/else
    
      if(empty($test_path))
      {
        $this->out('could not find a valid test path for test "%s"',$test_name);
        
      }
      else
      {
        $test_path_list[] = $test_path;
        
      }//if
    
    }//foreach
    
    $this->runTests($test_path_list);
    
    return false;
  
  }//method
  
  protected function runTests(array $test_list){
  
    // canary...
    if(empty($test_list)){ $this->out('No tests to run were found'); return; }//if
  
    $command = $this->request->getField('phpunit-path','phpunit');
  
    // add bootstrap here...
    $command .= sprintf(' --bootstrap "%s"',$this->getBootstrap());
    
    // add any other phpunit arguments...
    if($phpunit_args = $this->request->getField('phpunit-args','')){
    
      $command .= ' '.$phpunit_args;
    
    }//if
    
    // add filter (it is separate from phpunit-args because I use it way more)...
    if($filter = $this->request->getField('filter','')){
    
      $command .= sprintf(' --filter "%s"',$filter);
    
    }//if
  
    // add tests...
    $command .= sprintf(' "%s"',join('" "',$test_list));
    
    // add custom values...
    $command .= sprintf(' --app-path="%s"',$this->framework_config->getAppPath());
    
    $this->out('Running command: %s',$command);
    $this->out();
    
    /* echo sprintf(
      'Test(s) will use the "%s" environment and the "%s" application',
      $options['env'],
      $options['application']
    ),PHP_EOL,PHP_EOL; */
    
    $ret_int = 0;
    passthru($command,$ret_int);
    
    $this->out();
    $this->out();
    $this->out('Command returned: %s',$ret_int);
    
    return $ret_int;
    
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
  
  protected function findTest($test_name)
  {
    // canary...
    if(empty($test_name))
    {
      throw new \UnexpectedValueException('$test_name was empty');
    }//if
  
    $ret_path = '';
    $test_name = $this->normalizeTestName($test_name);
    $test_regex = sprintf('#%s$#i',$test_name);
  
    // first, check the main test dir...
    $test_dir = $this->getMainTestDir();
    $this->trace('Searching %s for %s',$test_dir,$test_name);
    
    ///$test_path = new Path($test_dir,$test_name);
    ///$this->trace('Checking main path: "%s"',$test_path);
    
    if($test_dir->exists() && ($test_path = $test_dir->getChild($test_regex)))
    {
      $ret_path = $test_path;
    }
    else
    {
      $test_dirs = $this->getSubTestDirs();
      foreach($test_dirs as $test_dir)
      {
        $this->trace('Searching %s for %s',$test_dir,$test_name);
        
        if($test_dir->exists() && ($test_path = $test_dir->getChild($test_regex)))
        {
          $ret_path = $test_path;
          break;
        }//if
      
        /* $test_path = new Path($test_dir,$test_name);
        
        $this->trace('Checking sub path: "%s"',$test_path);
        
        if($test_path->exists())
        {
          $ret_path = $test_path;
          break;
          
        }//if */
      
      }//foreach
    
    }//if
  
    if(!empty($ret_path)){
  
      $this->trace('Found test %s',$ret_path);
      
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
  protected function getMainTestDir()
  {
    $app_dir = $this->framework_config->getAppPath();
    return new Path($app_dir,'test');
  
  }//method
  
  protected function getSubTestDirs()
  {
    $ret_list = array();
  
    // get all the plugin test paths...
    $plugin_paths = $this->framework_config->getPluginPaths();
    foreach($plugin_paths as $plugin_path)
    {
      $ret_list[] = new Path($plugin_path,'test');
      
    }//foeach
    
    $ret_list[] = new Path($this->framework_config->getFrameworkPath(),'test');
  
    return $ret_list;
  
  }//method
  
  protected function getBootstrap(){
  
    return realpath(__DIR__.'/../../../config/bootstrap.php');
  
  }//metohd

}//class
