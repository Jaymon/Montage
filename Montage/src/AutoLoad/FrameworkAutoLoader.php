<?php

/**
 *  handle Framework autoloading
 *  
 *  this is a very specific autoloader that only loads classes from the framework src path
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 7-19-11
 *  @package montage
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Montage\AutoLoad;

use Montage\Autoload\AutoLoader;

class FrameworkAutoLoader extends Autoloader {

  protected $framework_src_path = '';

  public function __construct($framework_src_path){
  
    $this->framework_src_path = $framework_src_path;
  
  }//method
  
  /**
   *  this is what will do the actual loading of each autoloader
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name){
  
    $ret_bool = false;
    $file_name = $this->normalizeClassName($class_name);
    $count = 0;
    $file_name = str_replace('Montage','',$file_name,$count);

    if($count > 0){
    ///if(mb_strpos($file_name,'Montage') === 0){
    
      $file = $this->framework_src_path.$file_name;
      require($file);
    
    }//if
    
  }//method

}//class     
