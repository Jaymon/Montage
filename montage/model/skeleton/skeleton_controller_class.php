<?php

/**
 *  returns a skeleton controller
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 8-24-10
 *  @package montage
 *  @subpackage skeleton  
 ******************************************************************************/
class skeleton_controller extends skeleton_file {
  
  protected function start(){
  
    $this->template_file = 'skeleton/controller_tmpl.php';
    
  }//method
  
  protected function getDocblockDesc(){ return 'a controller class'; }//method
  
}//class     
