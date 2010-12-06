<?php

/**
 *  returns a skeleton start
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 8-24-10
 *  @package montage
 *  @subpackage skeleton  
 ******************************************************************************/
class skeleton_start extends skeleton_file {
  
  protected function start(){
  
    $this->template_file = 'skeleton/start_tmpl.php';
    
  }//method
  
  protected function getDocblockDesc(){ return 'a start class'; }//method
  
}//class     
