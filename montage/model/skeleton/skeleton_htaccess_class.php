<?php

/**
 *  returns a skeleton htaccess file
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 8-24-10
 *  @package montage
 *  @subpackage skeleton  
 ******************************************************************************/
class skeleton_htaccess extends skeleton_file {
  
  protected function start(){
  
    $this->template_file = 'skeleton/htaccess_tmpl.php';
    
  }//method
  
  protected function getDocblockDesc(){ return ''; }//method
  
}//class     
