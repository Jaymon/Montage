<?php

/**
 *  handle Framework autoloading
 *  
 *  this is a very specific autoloader that only loads classes from the framework's src path
 *  
 *  @version 0.2
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 7-19-11
 *  @package montage
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Montage\Autoload;

use Montage\Autoload\Autoloader;

class FrameworkAutoloader extends Autoloader {

  /**
   *  the name of the framework
   *
   *  in order to be valid, a class name will have to start with $name as the first 
   *  bit of the namespace 
   *      
   *  @since  7-23-11
   *  @var  string      
   */
  protected $name = '';

  /**
   *  where the framework's root source directory is located (without a trailing slash)
   *
   *  @var  string      
   */
  protected $src_path = '';

  public function __construct($name,$src_path){
  
    $this->name = $name;
    $this->src_path = $src_path;
  
  }//method
  
  /**
   *  this is what will do the actual loading of each autoloader
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name){
  
    $ret_bool = false;
    $file_name = $this->normalizeClassName($class_name);
    
    ///$count = 0;
    ///$file_name = str_replace('Montage','',$file_name,$count);

    $pos = mb_strpos($file_name,$this->name);
    $is_framework = ($pos === 0) || ($pos === 1);

    ///if($count > 0){
    if($is_framework){
    
      $file = $this->src_path.DIRECTORY_SEPARATOR.$file_name; 
      include($file);
    
    }//if
    
  }//method

}//class     
