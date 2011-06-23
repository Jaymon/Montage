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

    error_reporting($this->framework_config->getErrorLevel());
    ///error_reporting(E_ALL ^ E_USER_NOTICE);
    mb_internal_encoding($this->framework_config->getCharset());
    date_default_timezone_set($this->framework_config->getTimezone());

    // since debug isn't on let's not display the errors to the user and rely on logging...
    ini_set('display_errors',$this->framework_config->showErrors() ? 'on' : 'off'); 
    
    $container = $this->getContainer();
    
    // tell the container what classes we want to use for some of the interfaces that
    // have multiple children...
    $container->setPreferred(
      'Symfony\Component\HttpFoundation\SessionStorage\SessionStorageInterface',
      'Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage'
     );
     
    // start the error handler if it hasn't been started...
    $container->findInstance('Montage\Error');
  
  }//method

}//class
