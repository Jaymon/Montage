<?php
/**
 *  the kernal/core that translates the request to the response
 *  
 *  other names: handler, sequence, assembler, dispatcher, scheduler
 *  http://en.wikipedia.org/wiki/Montage_%28filmmaking%29  
 *  
 *  the best name might be Server
 *   
 *  @version 0.6
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage;

// these are the files needed to use this class, after this class is loaded, the
// autoloader should handle everything else...
require_once(__DIR__.'/Dependency/Injector.php');
require_once(__DIR__.'/Dependency/Reflection.php');
require_once(__DIR__.'/Path.php');
require_once(__DIR__.'/Field.php');

use Montage\Path;
use Montage\Field;

use Montage\Exceptions\NotFoundException;
use Montage\Exceptions\InternalRedirectException;
use Montage\Exceptions\RedirectException;
use Montage\Exceptions\StopException;

use out;

use Montage\Dependency\Reflection;
use Montage\Dependency\Container;
use Montage\Dependency\Injector;

class Handler extends Field implements Injector {

  protected $app_path = '';
  
  protected $framework_path = '';

  protected $field_map = array();
  
  /**
   *  holds the dependancy injection container instance
   *
   *  @var  \Montage\Dependancy\Container   
   */
  protected $container = null;

  public function __construct($env,$debug_level,$app_path){
  
    // canary...
    if(empty($env)){
      throw new \InvalidArgumentException('$env was empty, please set $env to something like "dev" or "prod"');
    }//if
    if(empty($app_path)){
      throw new \InvalidArgumentException('$app_path was empty, please set it to the root path of your app');
    }//if
  
    ///out::e($namespace,$debug_level,$app_path);
    
    $this->setField('env',$env);
    $this->setField('debug_level',$debug_level);
    
    $this->app_path = $app_path;
    $reflection = new Reflection();
    $reflection->addPath($this->getFrameworkPath());
    $reflection->addPath($app_path);
    
    $container_class_name = $reflection->findClassName('Montage\Dependency\Container');
    $container = new $container_class_name($reflection);
    // just in case, container should know about this instance for circular-dependency goodness...
    $container->setInstance($this);
    
    $this->setContainer($container);
    
  }//method
  
  public function handle(){
  
    $env = $this->getField('env');
    $container = $this->getContainer();
  
    try{
    
      // start the Config classes...
      $config_instance_map = array();
      // findInstance() for getBestInstance()?
      ///$config_instance_map[] = $this->classes->getInstance('Config\Config'); // global
      ///$config_instance_map[] = $this->classes->getInstance(sprintf('Config\%s',$this->field_map['env'])); // environment
      // $this->handleConfig();
    
      // get the request instance...
      $request = $container->findInstance('Montage\Request\Requestable');
    
      // decide where the request should be forwarded to...
      $forward = $container->findInstance('Montage\Controller\Forward');
      list($controller_class,$controller_method,$controller_method_params) = $forward->find(
        $request->getHost(),
        $request->getPath()
      );
      
      $ret_handle = $this->handleController($controller_class,$controller_method,$controller_method_params);
          
    }catch(Exception $e){
    
      $ret_mixed = $this->handleException($e);
    
    }//try/catch
  
  }//method

  protected function handleController($class_name,$method,array $params = array()){
  
    $container = $this->getContainer();
    
    $controller = $container->findInstance($class_name);
    $rmethod = new \ReflectionMethod($controller,$method);
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
  protected function handleException(Exception $e){
  
    $this->handleRecursion();
  
    $ret_mixed = null;
  
    try{
    
      // needs: Request, Forward
    
      if($e instanceof InternalRedirectException){
      
        list($controller_class,$controller_method,$controller_method_params) = $forward->find(
          $request->getHost(),
          $e->getPath()
        );
      
        $ret_mixed = $this->handleController($controller_class,$controller_method,$controller_method_params);
      
      }else if($e instanceof RedirectException){
      
      
      }else if($e instanceof StopException){
        
        // don't do anything, we're done
        
      }else{
        
        list($controller_class,$controller_method,$controller_method_params) = $forward->findException($e);
        
        $ret_mixed = $this->handleController($controller_class,$controller_method,$controller_method_params);
        
      }//try/catch
      
    }catch(Exception $e){
    
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
  protected function handleRecursion(){
  
    $max_ir_count = $this->getField('Handler.recursion_max_count',10);
    $ir_field = 'Handler.recursion_count'; 
    $ir_count = $this->getField($ir_field,0);
    if($ir_count > 10){
      throw new \RuntimeException(
        sprintf(
          'The application has internally redirected more than %s times, something seems to '
          .'be wrong and the app is bailing to avoid infinite recursion!',
          $max_ir_count
        )
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

  public function getFrameworkPath(){
  
    if(empty($this->framework_path)){
      $this->framework_path = __DIR__;
    }//if
  
    return $this->framework_path;
  
  }//method
  
  public function getAppPath(){
  
    return $this->app_path;
  
  }//method

}//method
