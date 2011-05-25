<?php
/**
 *  the kernal/core that translates the request to the response
 *  
 *  other names: handler, sequence, assembler
 *  http://en.wikipedia.org/wiki/Montage_%28filmmaking%29   
 *   
 *  @version 0.6
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage;

use Montage\Path;
use Montage\Classes;

use Montage\Interfaces\Coupling;
use Montage\Coupler;

///use Montage\Request\RequestInterface;

class Core implements Coupling {

  protected $app_path = '';
  
  protected $framework_path = '';

  protected $classes = null;

  protected $field_map = array();
  
  protected $coupler = null;

  public function __construct($env,$debug_level,$app_path){
  
    ///out::e($namespace,$debug_level,$app_path);
    
    $this->field_map['env'] = $env;
    $this->field_map['debug_level'] = $debug_level;
    
    $this->app_path = $app_path;
    $this->classes = new Classes();
    $this->classes->addPath($this->getFrameworkPath());
    $this->classes->addPath($app_path);
    
    $coupler_class = $this->classes->findClassName('Montage\Coupler');
    $coupler = new $coupler_class($this->classes);
    $this->setCoupler($coupler);
    
    spl_autoload_register(array($this->classes,'load'));
    
  }//method
  
  public function handle(){
  
    // start the Config classes...
    $config_instance_map = array();
    // findInstance() for getBestInstance()?
    ///$config_instance_map[] = $this->classes->getInstance('Config\Config'); // global
    ///$config_instance_map[] = $this->classes->getInstance(sprintf('Config\%s',$this->field_map['env'])); // environment
  
    $request = $this->classes->findInstance('Montage\Interfaces\Request');
  
    ///$forward = $this->classes->getInstance('Montage\Forward');
    
    
  
  
  
  
  
  }//method

  public function setCoupler(Coupling $coupler){
    $this->coupler = $coupler;
  }//method
  
  public function getCoupler(){ return $this->coupler; }//method

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
