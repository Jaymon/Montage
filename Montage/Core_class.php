<?php
/**
 *  this class handles all the discovering and auto-loading of classes, it also has
 *  methods to let the developer easily get class information and class relationships  
 *  
 *  this class can be mostly left alone unless you want to set more class paths 
 *  (use {@link setPath()}) than what are used by default, or if you want to add
 *  a custom autoloader (use {@link appendClassLoader()}) 
 *
 *  class paths checked by default:
 *    [MONTAGE_PATH]/model
 *    [MONTAGE_APP_PATH]/settings
 *    [MONTAGE_PATH]/plugins
 *    [MONTAGE_APP_PATH]/plugins  
 *    [MONTAGE_APP_PATH]/model
 *    [MONTAGE_APP_PATH]/controller/$controller
 *   
 *  @version 0.6
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 12-28-09
 *  @package montage 
 ******************************************************************************/
namespace Montage;

use Montage\Path;
use Montage\Classes;
///use Montage\Request\RequestInterface;

class Core {

  protected $app_path = '';
  
  protected $framework_path = '';

  protected $classes = null;

  protected $field_map = array();

  public function __construct($env,$debug_level,$app_path){
  
    ///out::e($namespace,$debug_level,$app_path);
    
    $this->field_map['env'] = $env;
    $this->field_map['debug_level'] = $debug_level;
    
    $this->app_path = $app_path;
    $this->classes = new Classes();
    $this->classes->addPath($this->getFrameworkPath());
    $this->classes->addPath($app_path);
    
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
