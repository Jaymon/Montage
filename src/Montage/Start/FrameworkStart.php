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
use Montage\Event\Event;

class FrameworkStart extends Start {

  /**
   *  normally start class takes a FrameworkConfig instance but we override parent::__construct()
   *  so we can create the FrameworkConfig instance in the handle() method of this class
   */
  public function __construct(){ parent::__construct(); }//method

  public function handle(){

    $this->createFrameworkConfig();
    
    $this->handleError();
    
    mb_internal_encoding($this->framework_config->getCharset());
    date_default_timezone_set($this->framework_config->getTimezone());
    
    $this->handleCreateEvents();
    $this->handleCreatedEvents();
    
    // now that all the creation events are handled, we can create objects and set defaults
    // if you try and create something before the handleCreate(d)Events() methods then
    // stuff that needs to be set might not be set...
    $response = $this->getContainer()->getResponse();
    $response->setTemplate('page');
    $response->setField('footer_template','footer');
  
  }//method
  
  /**
   *  handle setting events that will be triggered when certain objects have been created
   *
   *  @since  10-28-11
   */
  protected function handleCreatedEvents(){
  
    $event_dispatch = $this->getEventDispatch();
  
    $event_dispatch->listen(
      'framework.filter.created:\Montage\Response\Template',
      function(\Montage\Event\FilterEvent $event){

        $instance = $event->getParam();
        $container = $event->getField('container');

        $config = $container->getConfig();
        $instance->addPaths($config->getField('view_paths'));
        
      }
    );
    
    // allow form objects in the controller method to be populated with submitted values
    $event_dispatch->listen(
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
  
  /**
   *  handle setting events that will be triggered when certain objects are about to be created
   *
   *  @since  10-28-11
   */
  protected function handleCreateEvents(){
  
    $event_dispatch = $this->getEventDispatch();
  
    $event_dispatch->listen(
      'framework.filter.create:\Montage\Session',
      function(\Montage\Event\FilterEvent $event){

        $params = $event->getParam();
      
        if(!isset($params['storage']) && !isset($params[0])){
        
          // there are about 6 children of the SessionInterface, so we are choosing here which one
          // we want....
          $params['storage'] = new \Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage();
        
        }//if
      
        $event->setParam($params);
        
      }
    );
    
    // set up some lazy load dependency resolves...
    $event_dispatch->listen(
      'framework.filter.create:\Montage\Request\Requestable',
      function(\Montage\Event\FilterEvent $event){
      
        $params = $event->getParam();
      
        // set the values for the url instance on creation...
        $ret_map = array(
          'query' => $_GET,
          'request' => $_POST,
          'attributes' => array(),
          'cookies' => $_COOKIE,
          'files' => $_FILES,
          'server' => $_SERVER
        );
        
        $event->setParam(array_merge($ret_map,$params));
        
      }
    );
    
    $event_dispatch->listen(
      'framework.filter.create:\Montage\Url',
      function(\Montage\Event\FilterEvent $event){
      
        $params = $event->getParam();
        $container = $event->getField('container');
        $request = $container->getRequest(); 
        
        // set the values for the url instance on creation...
        $ret_map = array(
          'current_url' => $request->getUrl(),
          'base_url' => $request->getBase(),
          'use_domain' => $request->isCli()
        );
        
        $event->setParam(array_merge($ret_map,$params));
        
      }
    );
  
  }//method
  
  /**
   *  handle setting up all the error stuff
   *
   *  @since  10-28-11   
   */
  protected function handleError(){
    
    $container = $this->getContainer();
    
    error_reporting($this->framework_config->getErrorLevel());
    ///error_reporting(E_ALL ^ E_USER_NOTICE);
    
    ini_set('display_errors',$this->framework_config->showErrors() ? 'on' : 'off');
    
    // start/register the error handler if it hasn't been started...
    $container->getErrorHandler();
  
  }//method
  
  /**
   *  create the framework config
   *
   *  @see  http://teddziuba.com/2011/06/most-important-concept-systems-design.html
   *    the framework config should be our Single Point of Truth from here on out   
   *  
   *  @since  10-28-11
   */
  protected function createFrameworkConfig(){

    $container = $this->getContainer();
    $event_dispatch = $this->getEventDispatch();
    
    $event_dispatch->listen(
      'framework.filter.created:\Montage\Config\FrameworkConfig',
      function(\Montage\Event\FilterEvent $event){

        $instance = $event->getParam();
        $container = $event->getField('container');

        // move all the fields from the framework into the config...
        $framework = $container->getFramework();
        $instance->addFields($framework->getFields());
        $framework->setFields(array());
        
        /* $instance->setField('env',$framework->getField('env'));
        $instance->setField('debug_level',$framework->getField('debug_level'));
        $instance->setField('app_path',$framework->getField('app_path'));
        $instance->setField('framework_path',$framework->getField('framework_path'));
        $instance->setField('plugin_paths',$framework->getField('plugin_paths',array()));
        $instance->setField('cache_path',$framework->getField('cache_path'));
        */
        
      }
    );

    $this->framework_config = $container->getConfig();

  }//method

}//class
