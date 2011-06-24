<?php
/**
 *  the kernal/core that translates the request to the response
 *  
 *  other names: handler, sequence, assembler, dispatcher, scheduler
 *  http://en.wikipedia.org/wiki/Montage_%28filmmaking%29  
 *  
 *  the best name might be Server or Framework (I like Framework, it will most likely
 *  be changed to Framework at some point)
 *   
 *  @version 0.6
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage;

// these are the files needed to use this class, after this class is loaded, the
// autoloader should handle everything else...
require_once(__DIR__.'/Cache/Cacheable.php');
require_once(__DIR__.'/Cache/Cache.php');
require_once(__DIR__.'/Cache/ObjectCache.php');

require_once(__DIR__.'/Dependency/Dependable.php');
require_once(__DIR__.'/Dependency/ReflectionFile.php');
require_once(__DIR__.'/Dependency/Reflection.php');
require_once(__DIR__.'/Path.php');

require_once(__DIR__.'/Field/Fieldable.php');
require_once(__DIR__.'/Field/Field.php');

use Montage\Cache\Cache;
use Montage\Path;
use Montage\Field\Field;

use Montage\Exception\NotFoundException;
use Montage\Exception\InternalRedirectException;
use Montage\Exception\RedirectException;
use Montage\Exception\StopException;
use Exception;

use out;

use Montage\Dependency\Reflection;
use Montage\Dependency\Container;
use Montage\Dependency\Dependable;

class Framework extends Field implements Dependable {
  
  /**
   *  @var  Montage\Request\Requestable
   */
  protected $request = null;
  
  /**
   *  @var  Montage\Controller\Select
   */
  protected $controller_select = null;
  
  /**
   *  #var  Montage\Config\FrameworkConfig
   */
  protected $framework_config = null;
  
  /**
   *  holds the dependancy injection container instance
   *
   *  @var  \Montage\Dependancy\Container   
   */
  protected $container = null;

  /**
   *  create this object
   *  
   *  @param  string  $env  the environment, usually something like "dev" or "prod"
   *  @param  integer $debug_level  what level of debug you want
   *  @param  string  $app_path the root path for your application
   */
  public function __construct($env,$debug_level,$app_path){
  
    // canary...
    if(empty($env)){
      throw new \InvalidArgumentException('$env was empty, please set $env to something like "dev" or "prod"');
    }//if
    if(empty($app_path)){
      throw new \InvalidArgumentException('$app_path was empty, please set it to the root path of your app');
    }//if
    
    // collect all the paths we're going to use...
    $framework_path = __DIR__;
    
    // we shouldn't have "new" here, but sometimes you just have to break the rules
    // to make things easier, I didn't want to have to create a Cache and Reflection
    // instance to pass in here to resolve all the dependencies...
    // see: http://misko.hevery.com/2008/07/08/how-to-think-about-the-new-operator/ for how
    // I'm wrong about this, but convenience trumps rightness in this instance
  
    // since the container isn't built, let's build it...
    $reflection = new Reflection();
    
    // create the caching object that Reflection will use...
    $cache_path = new Path($app_path,'cache');
    $cache_path->assure();
    $cache = new Cache();
    $cache->setPath($cache_path);
    $cache->setNamespace($env);
    
    // load the cache...
    $reflection->setCache($cache);
    $reflection->importCache();
    
    // paths to add...
    $path_list = array(
      $framework_path,
      new Path($app_path,'vendor'),
      new Path($app_path,'plugins'),
      new Path($app_path,'src'),
      new Path($app_path,'config')
    );
    
    foreach($path_list as $path){
    
      if(is_dir($path)){

        $reflection->addPath($path);
        
      }//if
    
    }//foreach
    
    $container_class_name = $reflection->findClassName('Montage\Dependency\Container');
    $container = new $container_class_name($reflection);
    // just in case, container should know about this instance for circular-dependency goodness...
    $container->setInstance($this);
    $container->setInstance($cache);
    
    $this->setContainer($container);

    // set all the default configuration stuff...
    $this->framework_config = $container->findInstance('\Montage\Config\FrameworkConfig');
    $this->framework_config->setField('env',$env);
    $this->framework_config->setField('debug_level',$debug_level);
    $this->framework_config->setField('app_path',$app_path);
    $this->framework_config->setField('framework_path',$this->getField('framework_path'));
    
  }//method
  
  /**
   *  call this method to actually handle the request
   *  
   *  once this method is called, everything is taken care of for you
   */
  public function handle(){
  
    $container = $this->getContainer();
  
    try{

      // start the START classes...
      $this->handleStart();
    
      // get the request instance...
      $this->request = $container->findInstance('Montage\Request\Requestable');
    
      // decide where the request should be forwarded to...
      list($controller_class,$controller_method,$controller_method_params) = $this->getControllerSelect()->find(
        $this->request->getHost(),
        $this->request->getPath()
      );
      
      $ret_handle = $this->handleController($controller_class,$controller_method,$controller_method_params);
          
    }catch(Exception $e){
    
      $ret_mixed = $this->handleException($e);
    
    }//try/catch
  
  }//method

  /**
   *  start all the known \Montage\Start\Startable classes
   *  
   *  a Start class is a class that will do configuration stuff
   */
  protected function handleStart(){
  
    $instance_list = array();
    $env = $this->framework_config->getEnv();
    $container = $this->getContainer();
    $select = $container->findInstance('\Montage\Start\Select');
    
    $start_class_list = $select->find($env);
    $method_name = $select->getMethod();
    
    foreach($start_class_list as $i => $class_name){
    
      $instance_list[$i] = $container->getInstance($class_name);
      $container->callMethod($instance_list[$i],$method_name);
      
    }//foreach
     
  }//method

  /**
   *  create a controller instance and call that instance's $method to handle the request
   *  
   *  @param  string  $class_name the controller class name
   *  @param  string  $method the method that will be called
   *  @param  array $params the arguments that will be passed to the $class_name->$method() call
   */
  protected function handleController($class_name,$method,array $params = array()){
  
    $container = $this->getContainer();
    
    $controller = $container->findInstance($class_name);
    $rmethod = new \ReflectionMethod($controller,$method);
    
    // if the first param is an array, then it will take all the passed in $params...
    // quick/nice way to do a catch-all...
    $rmethod_params = $rmethod->getParameters();
    if(isset($rmethod_params[0])){
    
      if($rmethod_params[0]->isArray()){
        $params = array($params);
      }//if
    
    }//if
    
    $rmethod_params = $container->normalizeParams($rmethod,$params);
    
    // make sure there are enough required params...
    $required_param_count = $rmethod->getNumberOfParameters();
    if($required_param_count !== count($rmethod_params)){
      throw new NotFoundException(
        sprintf(
          '%s::%s expects %s arguments to be passed to it, but %s args were passed',
          $class_name,
          $method,
          $required_param_count,
          count($rmethod_params)
        )
      );
    }//if
    
    $controller->preHandle();
    $ret_mixed = $rmethod->invokeArgs($controller,$rmethod_params);
    $controller->postHandle();
    
    return $ret_mixed;
  
  }//method
  
  /**
   *  handle a thrown exception
   *  
   *  @return boolean $use_template
   */
  protected function handleException(\Exception $e){
  
    $this->handleRecursion($e);
  
    $ret_mixed = null;
  
    try{
    
      // needs: Request, Forward
    
      if($e instanceof InternalRedirectException){
      
        list($controller_class,$controller_method,$controller_method_params) = $this->getControllerSelect()->find(
          $request->getHost(),
          $e->getPath()
        );
      
        $ret_mixed = $this->handleController($controller_class,$controller_method,$controller_method_params);
      
      }else if($e instanceof RedirectException){
      
      
      }else if($e instanceof StopException){
        
        // don't do anything, we're done
        
      }else{
        
        list($controller_class,$controller_method,$controller_method_params) = $this->getControllerSelect()->findException($e);
        
        $ret_mixed = $this->handleController($controller_class,$controller_method,$controller_method_params);
        
      }//try/catch
      
    }catch(\Exception $e){
    
      $ret_mixed = $this->handleException($e);
    
    }//try/catch
  
    return $ret_mixed;
  
  }//method
  
  /**
   *  check for infinite recursion, throw an exception if found
   *  
   *  this is done by keeping an internal count of how many times this method has been called, 
   *  if that count reaches the max count then an exception is thrown
   *  
   *  @return integer the current count
   */
  protected function handleRecursion(\Exception $e){
  
    $max_ir_count = $this->getField('Handler.recursion_max_count',10);
    $ir_field = 'Handler.recursion_count'; 
    $ir_count = $this->getField($ir_field,0);
    if($ir_count > 10){
      throw new \RuntimeException(
        sprintf(
          'The application has internally redirected more than %s times, something seems to '
          .'be wrong and the app is bailing to avoid infinite recursion!',
          $max_ir_count
        ),
        $e->getCode(),
        $e
      );
    }else{
    
      $ir_count = $this->bumpField($ir_field,1);
      
    }//if/else
    
    return $ir_count;
  
  }//method

  public function setContainer(\Montage\Dependency\Container $container){
    $this->container = $container;
  }//method
  
  public function getContainer(){ return $this->container; }//method

  public function getControllerSelect(){
  
    // canary...
    if(!empty($this->controller_select)){ return $this->controller_select; }//if
    
    $container = $this->getContainer();
    $this->controller_select = $container->findInstance('Montage\Controller\Select');
    return $this->controller_select;
  
  }//method

}//method
