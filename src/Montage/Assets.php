<?php
/**
 *  handle session stuff 
 *
 *  how I'm imagining assets working: it will go through and compile the list of all assets
 *  and move them to the web/assets folder and keep them in memory, then there will be a 
 *  __toString method that will output the js and css at the top of page, so anything can
 *  be overriden, http assets can be added, etc.
 *  
 *  @link http://guides.rubyonrails.org/asset_pipeline.html
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
   *  holds where the paths in the {@link $path_list} will be moved to
   *  
   *  @since  9-19-11   
   *  @see  setToPath()      
   *  @var  Path
   */
  protected $to_path = null;

  ///public function addCss($

  public function setToPath($path){
  
    $this->to_path = $this->normalizePath($path);
  
  }//method

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
  
    $path = $this->normalizePath($path);
    $this->path_list[] = $path;
    return $this;
  
  }//method
  
  public function addPaths(array $path_list){
  
    foreach($path_list as $path){ $this->addPath($path); }//foreach
    
    return $this;
  
  }//method
  
  /**
   *  normalize the passed in path
   *
   *  @param  string  $path
   *  @return Path   
   */
  protected function normalizePath($path){
  
    // canary...
    if(empty($path)){
      throw new \UnexpectedValueException('$path is empty');
    }//if
    if(!($path instanceof Path)){
      $path = new Path($path);
    }//if
    
    return $path;
  
  }//method
  
}//class
