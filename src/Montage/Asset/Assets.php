<?php
/**
 *  handle assets
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
namespace Montage\Asset;

use Montage\Path;

class Assets extends Asset {

  /**
   *  hold all the paths this instance will use to find the template file
   *  
   *  @var  array
   */
  protected $src_path_list = array();
  
  /* public function __construct($src_path_list){
  
    $this->src_path_list = (array)$src_path_list;
  
  }//method */
  
  ///public function getSrcPaths(){ return $this->src_path_list; }//method
  
}//class
