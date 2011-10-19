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

  /**
   *  normally start class takes a FrameworkConfig instance but we override parent::__construct()
   *  so we can create the FrameworkConfig instance in the handle() method of this class
   */
  public function __construct(){ parent::__construct(); }//method

  public function handle(){

    $container = $this->getContainer();
      
    $container->onCreated(
      '\Montage\Config\FrameworkConfig',
      function($container,$instance){

        // the framework config should be our Single Point of Truth from here on out
        // http://teddziuba.com/2011/06/most-important-concept-systems-design.html
        $framework = $container->getFramework();
        $instance->setField('env',$framework->getField('env'));
        $instance->setField('debug_level',$framework->getField('debug_level'));
        $instance->setField('app_path',$framework->getField('app_path'));
        $instance->setField('framework_path',$framework->getField('framework_path'));
        $instance->setField('plugin_paths',$framework->getField('plugin_paths',array()));
        $instance->setField('cache_path',$framework->getField('cache_path'));
        
      }
    );

    $this->framework_config = $container->getConfig();

    error_reporting($this->framework_config->getErrorLevel());
    ///error_reporting(E_ALL ^ E_USER_NOTICE);
    mb_internal_encoding($this->framework_config->getCharset());
    date_default_timezone_set($this->framework_config->getTimezone());

    ini_set('display_errors',$this->framework_config->showErrors() ? 'on' : 'off'); 
    
    $container->onCreate(
      'Montage\Session',
      function($container,array $params = array()){
      
        if(!isset($params['storage']) && !isset($params[0])){
        
          // there are about 6 children of the SessionInterface, so we are choosing here which one
          // we want....
          $params['storage'] = new \Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage();
        
        }//if
      
        return $params;
        
      }
    );
    
    // set up some lazy load dependency resolves...
    $container->onCreate(
      'Montage\Request\Requestable',
      function($container,array $params = array()){
      
        // set the values for the url instance on creation...
        $ret_map = array(
          'query' => $_GET,
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

        $request = $container->getRequest();
        
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

        $framework = $container->getFramework();
        $instance->addPaths($framework->getField('view_paths'));
        
      }
    );
    
    // start/register the error handler if it hasn't been started...
    $container->getInstance('Montage\Error');
    
    // set some events...
    $dispatch = $this->getEventDispatch();
    
    // allow form objects in the controller method to be populated with submitted values
    $dispatch->listen(
      'framework.filter.controller_param_created',
      function(\Montage\Event\FilterEvent $event){
      
        $instance = $event->getParam();
      
        // canary...
        if(!($instance instanceof \Montage\Form\Form)){ return; }//if
      
        $container = $event->getField('container');
        $request = $container->getRequest();
        
        $form_name = $instance->getName();

        if($form_field_map = $request->getField($form_name)){
        
          $instance->set($form_field_map);
        
        }//if
        
        $event->setParam($instance);
        
      }
    );
  
  }//method

}//class
