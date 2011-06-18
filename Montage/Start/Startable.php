<?php
/**
 *  all start classes should use this interface 
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Start  
 ******************************************************************************/      
namespace Montage\Start;

interface Startable {

  /**
   *  all configuration code needed on startup should go in this method
   */
  public function handle();
  
}//interface
