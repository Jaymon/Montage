<?php
/**
 *  handle an asset  
 * 
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 9-23-11
 *  @package montage
 ******************************************************************************/
namespace Montage\Asset;

class FrameworkAssets extends Assets {
  
  protected $instance_list = array();
  
  public function addInstance(Assets $instance){
  
    $this->instance_list[] = $instance;
  
  }//method
  
  public function handle(){
  
    $dest_path = $this->getDestPath();
  
    foreach($this->instance_list as $instance){
    
      $instance->setDestPath($this->getDestPath(),$this->relative_path);
      $instance->handle();
      
      $assets = $instance->getAssets();
    
    
    }//foreach
  
  
  
  }//method
  
}//class
