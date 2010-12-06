<?php

/**
 *  returns a skeleton index
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 8-24-10
 *  @package montage
 *  @subpackage skeleton  
 ******************************************************************************/
class skeleton_index extends skeleton_file {
  
  protected function start(){
  
    $this->template_file = 'skeleton/index_tmpl.php';
    
  }//method
  
  protected function getDocblockDesc(){ return 'Public facing interface to montage'; }//method
  
}//class     
