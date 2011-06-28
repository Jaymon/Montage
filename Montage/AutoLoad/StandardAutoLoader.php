<?php

/**
 *  handle generic autoloading
 *  
 *  implements the portable autoloader requirements:
 *  http://groups.google.com/group/php-standards/web/psr-0-final-proposal  
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-27-11
 *  @package montage
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Montage\AutoLoad;

use Montage\Autoload\AutoLoader;
use Montage\Path;

class StandardAutoLoader extends Autoloader {

  protected $path_list = array();
  
  protected $all_path_list = array();

  public function addPaths(array $path_list){
  
    foreach($path_list as $path){ $this->addPath($path); }//foreach
  
  }//method

  public function addPath($path){
  
    $path = new Path($path);
    if(!$path->isDir()){
      throw new \InvalidArgumentException(sprintf('$path (%s) is not a valid dir',$path));
    }//if
    
    $this->path_list[] = $path;
  
  }//method

  protected function getAllPaths(){
  
    if(!empty($this->all_path_list)){ return $this->all_path_list; }//if
    
    $include_path_list = explode(PATH_SEPARATOR,get_include_path());
    
    $this->all_path_list = array_merge($this->path_list,$include_path_list);
    return $this->all_path_list;
  
  }//method

  /**
   *  this is what will do the actual loading of each autoloader
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name){

    $ret_bool = false;
    $class_name = ltrim($class_name, '\\'); // get rid of absolute
    $namespace = array();
    
    $class_bits = explode('\\',$class_name);
    if(isset($class_bits[1])){
      
      $class_name = $class_bits[1];
      $namespace = array_slice($class_bits,0,-1);
      
    }//if
    
    $file_name = 
      join(DIRECTORY_SEPARATOR,$namespace)
      .DIRECTORY_SEPARATOR.
      str_replace('_',DIRECTORY_SEPARATOR,$class_name)
      .'.php';

    foreach($this->getAllPaths() as $path){
    
      $file = new Path($path,$file_name);
      if($file->isFile()){
        require($file);
        $ret_bool = true;
        break;
      }//if
    
    }//foreach
    
    return $ret_bool;
  
  }//method

}//class     
