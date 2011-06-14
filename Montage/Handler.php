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
      throw new InvalidArgumentException('$env was empty, please set $env to something like "dev" or "prod"');
    }//if
    if(empty($app_path)){
      throw new InvalidArgumentException('$app_path was empty, please set it to the root path of your app');
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
  
    // start the Config classes...
    $config_instance_map = array();
    // findInstance() for getBestInstance()?
    ///$config_instance_map[] = $this->classes->getInstance('Config\Config'); // global
    ///$config_instance_map[] = $this->classes->getInstance(sprintf('Config\%s',$this->field_map['env'])); // environment
  
    // get the request instance...
    $request = $container->findInstance('Montage\Request\Requestable');
  
    // decide where the request should be forwarded to...
    $forward = $container->findInstance('Montage\Controller\Forward');
    list($controller_class,$controller_method,$controller_method_params) = $forward->find(
      $request->getHost(),
      $request->getPath()
    );
    
    while(true){
      
      try{
      
        $this->handleController($controller_class,$controller_method,$controller_method_params);
        
      }catch(Exception $e){
      
        list($controller_class,$controller_method,$controller_method_params) = $forward->findException($e);
      
      }//try/catch
  
    }//while
  
  }//method

  protected function handleController($class_name,$method,array $params = array()){
  
    out::e($class_name,$method,$params);
  
    $container = $this->getContainer();
    
    $controller = $container->findInstance($class_name);
    $rmethod = new \ReflectionMethod($controller,$method);
    $rmethod_params = $container->normalizeParams($rmethod,$params);
    
    // make sure there are enough required params...
    $required_param_count = $rmethod->getNumberOfParameters();
    if($required_param_count !== count($rmethod_params)){
      throw new \LengthException(
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
