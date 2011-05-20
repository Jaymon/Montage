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

class Core {

  protected $app_path = '';
  
  protected $framework_path = '';

  public function __construct($namespace,$debug_level,$app_path){
  
    ///out::e($namespace,$debug_level,$app_path);
    
    $this->app_path = $app_path;
    $class = new Classes();
    $class->addPath($this->getFrameworkPath());
    $class->addPath($app_path);
    
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
