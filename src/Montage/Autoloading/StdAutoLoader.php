<?php

/**
 *  handle generic autoloading
 *  
 *  implements the portable autoloader requirements:
 *  http://groups.google.com/group/php-standards/web/psr-0-final-proposal  
 *  
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-27-11
 *  @package montage
 *  @subpackage Autoload 
 ******************************************************************************/
namespace Montage\Autoload;

use Montage\Autoload\Autoloader;
use Montage\Path;
use Montage\Cacheable

class StdAutoLoader extends Autoloader implements Cacheable {

  /**
   *  holds the user submitted paths
   *  
   *  @var  array
   */
  protected $path_list = array();
  
  /**
   *  get all the user submitted paths
   *   
   *  @since  7-22-11
   *  @return array a lit of paths   
   */
  public function getPaths(){ return $this->path_list; }//method

  /**
   *  add all paths in the list to the path list
   *  
   *  @param  array $path_list  a list of paths
   */
  public function addPaths(array $path_list){
  
    foreach($path_list as $path){ $this->addPath($path); }//foreach
  
  }//method

  /**
   *  add one path to the list of paths
   *
   *  @param  string  $path the path to add to the user defined paths
   */
  public function addPath($path){
  
    $path = new Path($path);
    $this->path_list[] = $path;
  
  }//method

  /**
   *  this is what will do the actual loading of each autoloader
   *  
   *  @param  string  $class_name
   */
  public function handle($class_name){
  
    $ret_bool = false;
    $file_name = $this->normalizeClassName($class_name);
    
    foreach($this->getPaths() as $path){
    
      $ret_bool = $this->req($path,$file_name);
      if($ret_bool){ break; }//if
    
    }//foreach
    
    if($ret_bool === false){
      
      foreach($this->getIncludePaths() as $path){
      
        $ret_bool = $this->req($path,$file_name);
        if($ret_bool){ break; }//if
      
      }//foreach
    
    }//if
    
    return $ret_bool;
  
  }//method

  /**
   *  require the full path if it exists
   *     
   *  @since  7-22-11
   *  @param  string  $path the base path without a trailing slash
   *  @param  string  $file_name  the file that will be appended to the path      
   *  @return boolean true if the file was included
   */
  protected function req($path,$file_name){
    
    $ret_bool = false;
    $file = $path.DIRECTORY_SEPARATOR.$file_name;

    if(is_file($file)){
      
      require($file);
      $ret_bool = true;
      
    }//if
    
    return $ret_bool;

  }//method
  
  /**
   *  get all the global application defined included paths
   *  
   *  I originally cached this, but that didn't allow future additions to the include path after the
   *  cache was set, so now it gets exploded everytime since it is only called when everything else fails
   *  I figure this isn't a big deal      
   *      
   *  @return array all the included paths, in an array
   */
  protected function getIncludePaths(){
  
    return explode(PATH_SEPARATOR,get_include_path());
  
  }//method

}//class     
