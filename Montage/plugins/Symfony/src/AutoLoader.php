<?php

/**
 *  handle autoloading the Symfony components
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-27-11
 *  @package mingo
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Symfony;

use Montage\AutoLoad\StandardAutoLoader;

class AutoLoader extends StandardAutoLoader {

  public function __construct(){
  
    $path = realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'vendor');
    $this->all_path_list = array($path);
  
  }//method

  /**
   *  this is just to satisfy the interface
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name){
    
    // strip off the symfony part of the namespace...
    $count = 0;
    $class_name = str_replace('Symfony\Component','',$class_name,$count);
    
    return ($count > 0) ? parent::handle($class_name) : false;
  
  }//method

}//class     
