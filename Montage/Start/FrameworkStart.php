<?php
/**
 *  this starts the montage framework and does configuration for just the framework
 *  
 *  this class should only be extended if you want to change core framework configuration
 *  and shouldn't be extended from stuff like Plugin startup classes (those should
 *  all extend Montage\Start\Start like this class does)    
 *  
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 6-17-11
 *  @package montage
 *  @subpackage Start  
 ******************************************************************************/
namespace Montage\Start;

use Montage\Start\Start;

class FrameworkStart extends Start {

  public function handle(){

    error_reporting($this->config->getErrorLevel());
    mb_internal_encoding($this->config->getCharset());
    date_default_timezone_set($this->config->getTimezone());

    // since debug isn't on let's not display the errors to the user and rely on logging...
    ini_set('display_errors',$this->config->showErrors() ? 'on' : 'off');  
  
  }//method

}//class
