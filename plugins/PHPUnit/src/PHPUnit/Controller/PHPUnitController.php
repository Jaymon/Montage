<?php
/**
 *  handle PHPUnit command line calling
 *   
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 7-29-11
 *  @package PHPUnit
 *  @subpackage Controller
 ******************************************************************************/
namespace PHPUnit\Controller;

use Montage\Controller\CliController;
use Montage\Framework;
use Montage\Config\FrameworkConfig;
use Montage\Path;
use Montage\Response\Template;

class PHPUnitController extends CliController {

  protected $framework = null;

  protected $framework_config = null;
  
  protected $tmpl = null;

  public function __construct(Framework $framework,FrameworkConfig $framework_config,Template $tmpl){
  
    $this->framework = $framework;
  
    $this->framework_config = $framework_config;
    
    $this->tmpl = $tmpl;
  
  }//method

  /**
   *  default command is to print out help
   *
   *  @param  array $params   
   */
  public function handleIndex(array $params = array()){ return $this->handleHelp($params); }//method
  
  /**
   *  actually run the passed in tests
   *  
   *  example: php montage.php phpunit/test TEST1 [TEST2 ...]
   *
   *  @param  array $test_list  the passed in tests
   */
  public function handleTest(array $test_list = array()){
  
    $test_path_list = array();
  
    // find and add all the passed in test paths...
    foreach($test_list as $test_name)
    {
      $test_path_list[] = $this->findTest($test_name);
    
    }//foreach
    
    $this->runTests($test_path_list);
    
    return false;
  
  }//method
  
  /**
   *  run the found tests
   *
   *  this is the method that actually calls phpunit
   *      
   *  @param  array $test_list  a list of tests to be ran
   *  @return integer the return code from the tests being run   
   */
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
    ///$command .= sprintf(' --app-path="%s"',$this->framework_config->getAppPath());
    
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
   *  find the test
   *
   *  @param  string  $test_name
   *  @return string  the full test path   
   */           
  protected function findTest($test_name)
  {
    // canary...
    if(empty($test_name))
    {
      throw new \UnexpectedValueException('$test_name was empty');
    }//if
    if(file_exists($test_name)){ return $test_name; }//if
  
    $ret_path = '';
    $test_name = $this->normalizeTestName($test_name);
    $test_regex = sprintf('#%s$#i',preg_quote($test_name));
  
    // first, check the main test dir...
    $test_dir = $this->getMainTestDir();
    
    $this->trace('Searching for %s in...',$test_name);
    
    $this->trace('  %s',$test_dir);

    if($test_dir->exists() && ($test_path = $test_dir->getChild($test_regex)))
    {
      $ret_path = $test_path;
    }
    else
    {
      $test_dirs = $this->getSubTestDirs();
      foreach($test_dirs as $test_dir)
      {
        $this->trace('  %s',$test_dir);
        
        if($test_dir->exists() && ($test_path = $test_dir->getChild($test_regex)))
        {
          $ret_path = $test_path;
          break;
        }//if
      
      }//foreach
    
    }//if
  
    if(!empty($ret_path)){
  
      $this->trace('Found test %s',$ret_path);
      
    }//if
  
    return $ret_path;
  
  }//method
  
  /**
   *  Converts something like 'User' to 'UserTest.php'
   *  
   *  @example
   *    in: User  out: UserTest.php
   *    in: Foo\namespace\User  out: Foo/namespace/UserTest.php            
   *  
   *  @param  string  $test_name
   *  @return string  the $test_name standardized
   */
  protected function normalizeTestName($test_name){
  
    // make sure it ends with .php...
    if(!preg_match('#\.php$#i',$test_name)){
    
      // add Test before the .php if it isn't there...
      if(!preg_match('#Test$#i',$test_name)){ $test_name .= 'Test'; }//if
      
      // final name should be <NAME>Test.php
      $test_name .= '.php';
      
    }//if
    
    // convert any namespace to dir separator...
    $test_name = str_replace('\\',DIRECTORY_SEPARATOR,$test_name);
  
    return $test_name;
  
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
  
  /**
   *  get the sub test dirs
   *  
   *  the sub dirs are all the secondary test directories the test file might be in
   *  if it isn't in the {@link getMainTestDir()}       
   *
   *  @return array   
   */
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
  
  /**
   *  get the bootstrap file that will be used when calling phpunit
   *  
   *  this builds the boostrap file and saves it in the temp dir for every test
   *  request, I thought about caching, but I'm not sure it is that big of a deal.
   *  The great thing about doing it this way is it allows overriding of all the 
   *  stuff         
   *      
   *  @since  7-28-11
   *  @return string  the file path
   */
  protected function getBootstrap(){
  
    $this->tmpl->setTemplate('bootstrap.php');
  
    $this->tmpl->setField('author',sprintf('Auto-generated by %s on %s',get_class($this),date(DATE_RFC822)));
  
    $framework_class_name = get_class($this->framework);
    $this->tmpl->setField('framework_class_name',$framework_class_name);
  
    // find all the framework paths...
    $rframework = new \ReflectionClass($framework_class_name);
    
    $framework_parent_list = array();
    $framework_interface_list = array();
    
    $framework_parent_list[] = $rframework->getFileName();
    
    $rparent = $rframework->getParentClass();
    do{
    
      if($rparent = $rparent->getParentClass()){
      
        $framework_parent_list[] = $rparent->getFileName();
      
      }//if
    
    }while(!empty($rparent));
    
    foreach($rframework->getInterfaces() as $rinterface){
    
      $framework_interface_list[] = $rinterface->getFileName();
    
    }//foreach
    
    $this->tmpl->setField(
      'framework_path_list',
      array_merge(
        $framework_interface_list,
        array_reverse($framework_parent_list)
      )
    );
    
    $this->tmpl->setField('app_path',$this->framework_config->getAppPath());
    
    $temp_file = tempnam(sys_get_temp_dir(),__CLASS__);
    
    // write out the bootsrap to the temp file...
    file_put_contents($temp_file,$this->tmpl->handle(Template::OUT_STR),LOCK_EX);
  
    return $temp_file;
  
  }//metohd

}//class
