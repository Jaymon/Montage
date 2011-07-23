<?php
/**
 *  handle session stuff 
 *
 *  @version 0.3
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 2-28-10
 *  @package montage
 ******************************************************************************/
namespace Montage;

use Montage\Path;

class Assets {

  /**
   *  hold all the paths this instance will use to find the template file
   *  
   *  @var  array
   */
  protected $path_list = array();

  /**
   *  if the class should check more than one place for the template, add the alternate
   *  paths using this, setTemplate will then go through all the paths checking
   *  to see if the file exists in any path
   *  
   *  we render the template files using the include_paths so you can set other template files
   *  in the actual template and include them without having to actually know the path, it's "all
   *  for your convenience" programming
   *  
   *  @param  string  $path the template path to add to the include paths
   */
  public function addPath($path){
  
    // canary...
    if(empty($path)){
      throw new \UnexpectedValueException('$path is empty');
    }//if
    if(!($path instanceof Path)){
      $path = new Path($path);
    }//if
  
    $this->path_list[] = $path;
    return $this;
  
  }//method
  
  public function addPaths(array $path_list){
  
    foreach($path_list as $path){ $this->addPath($path); }//foreach
    
    return $this;
  
  }//method
  
}//class
