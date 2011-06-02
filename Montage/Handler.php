<?php
/**
 *  the kernal/core that translates the request to the response
 *  
 *  other names: handler, sequence, assembler, dispatcher, scheduler
 *  http://en.wikipedia.org/wiki/Montage_%28filmmaking%29   
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

use Montage\Path;
use out;

use Montage\Dependency\Reflection;
use Montage\Dependency\Container;
use Montage\Dependency\Injector;

class Handler implements Injector {

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
  
    ///out::e($namespace,$debug_level,$app_path);
    
    $this->field_map['env'] = $env;
    $this->field_map['debug_level'] = $debug_level;
    
    $this->app_path = $app_path;
    $reflection = new Reflection();
    $reflection->addPath($this->getFrameworkPath());
    $reflection->addPath($app_path);
    
    $container_class_name = $reflection->findClassName('Montage\Dependency\Container');
    $container = new $container_class_name($reflection);
    $this->setContainer($container);
    
  }//method
  
  public function handle(){
  
    $r = new \ReflectionObject($this->getContainer());
    $rconstructor = $r->getConstructor();
    $rparams = $rconstructor->getParameters();
    ///out::i($rconstructor);
    foreach($rparams as $rparam){
      out::i($rparam);
      out::i($rparam->getClass());
    }//method
  
  
    // start the Config classes...
    $config_instance_map = array();
    // findInstance() for getBestInstance()?
    ///$config_instance_map[] = $this->classes->getInstance('Config\Config'); // global
    ///$config_instance_map[] = $this->classes->getInstance(sprintf('Config\%s',$this->field_map['env'])); // environment
  
    ///$request = $this->classes->findInstance('Montage\Interfaces\Request');
  
    ///$forward = $this->classes->getInstance('Montage\Forward');
    
    
  
  
  
  
  
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
