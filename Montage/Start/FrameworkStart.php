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
     
     // set up some lazy load dependency resolves...
    $container->onCreate(
      'Montage\Request\Requestable',
      function($container,array $params = array()){
      
        // set the values for the url instance on creation...
        $ret_map = array(
          'query' => (strncasecmp(PHP_SAPI, 'cli', 3) === 0) ? $_SERVER['argv'] : $_GET,
          'request' => $_POST,
          'attributes' => array(),
          'cookies' => $_COOKIE,
          'files' => $_FILES,
          'server' => $_SERVER
        );
        
        return array_merge($ret_map,$params);
        
      }
    );
    
    $container->onCreate(
      'Montage\Url',
      function($container,array $params = array()){

        $request = $container->findInstance('Montage\Request\Requestable');
        
        // set the values for the url instance on creation...
        $ret_map = array(
          'current_url' => $request->getUrl(),
          'base_url' => $request->getBase()
        );
        
        return array_merge($ret_map,$params);
        
      }
    );
    
    $container->onCreated(
      '\Montage\Response\Template',
      function($container,$instance){

        $framework = $container->findInstance('Montage\Framework');
        $instance->addPaths($framework->getField('view_paths'));
        
      }
    );
    
    // start the error handler if it hasn't been started...
    $container->findInstance('Montage\Error');
  
  }//method

}//class
