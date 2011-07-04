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

use Montage\Path;

class BlahAutoLoader {

  protected $path_list = array();
  
  protected $all_path_list = array();

  /**
   *  register this class as an autoloader
   */
  public function register(){
    spl_autoload_register(array($this,'handle'));
  }//method
  
  /**
   *  unregister this class as an autoloader
   */
  public function unregister(){
    spl_autoload_unregister(array($this,'handle'));
  }//method

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

    $oc = $class_name;

    $ret_bool = false;
    $class_name = ltrim($class_name, '\\'); // get rid of absolute
    $namespace = array();
    
    $class_bits = explode('\\',$class_name);
    
    // if a second item was set then there is a namespace...
    if(isset($class_bits[1])){
      
      end($class_bits);
      $class_name = $class_bits[key($class_bits)]; // last bit is the class name
      $namespace = array_slice($class_bits,0,-1); // everything else is namespace
      
    }//if
    
    $file_name = 
      join(DIRECTORY_SEPARATOR,$namespace)
      .DIRECTORY_SEPARATOR.
      str_replace('_',DIRECTORY_SEPARATOR,$class_name)
      .'.php';

    foreach($this->getAllPaths() as $path){
    
      $file = $path.DIRECTORY_SEPARATOR.$file_name;

      if(is_file($file)){
        
        require($file);
        $ret_bool = true;
        break;
        
      }//if
    
    }//foreach
    
    \out::e(''.sprintf('%s - %s - %s - class_exists(%s)',$oc,get_class($this),$ret_bool ? 'TRUE' : 'FALSE',class_exists($oc) ? 'TRUE' : 'FALSE'));
    return $ret_bool;
  
  }//method

}//class     
