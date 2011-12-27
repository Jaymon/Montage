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

  public function handle(){
    
    $this->handleError();
    
    mb_internal_encoding($this->config->getCharset());
    date_default_timezone_set($this->config->getTimezone());
    
    $this->handleCreateEvents();
    $this->handleCreatedEvents();
    
    // now that all the creation events are handled, we can create objects and set defaults
    // if you try and create something before the handleCreate(d)Events() methods then
    // stuff that needs to be set might not be set...
  
  }//method
  
  /**
   *  handle setting events that will be triggered when certain objects have been created
   *
   *  @since  10-28-11
   */
  protected function handleCreatedEvents(){
  
    $event_dispatch = $this->getEventDispatch();
  
    $event_dispatch->listen(
      'framework.filter.created:\\Montage\\Response\\Response',
      function(\Montage\Event\FilterEvent $event){

        $instance = $event->getParam();
        
        // only set these values if nothing has been set
        if(!$instance->hasTemplate()){ $instance->setTemplate('page'); }//if
        if(!$instance->hasField('footer_template')){
          $instance->setField('footer_template','footer');
        }//if
        
      }//closure
    );
  
    $event_dispatch->listen(
      'framework.filter.created:\\Montage\\Response\\Template',
      function(\Montage\Event\FilterEvent $event){

        $instance = $event->getParam();
        $container = $event->getField('container');

        $config = $container->getConfig();
        $instance->addPaths($config->getField('view_paths'));
        
      }//closure
    );
    
    // automatically import the cache if the class implements the right interface
    $event_dispatch->listen(
      'framework.filter.created:\\Montage\\Cache\\Cacheable',
      function(\Montage\Event\FilterEvent $event){

        $instance = $event->getParam();
        if($cache = $instance->getCache()){
        
          $instance->importCache();
          
        }//if
        
      }//closure
    );
    
    // automatically add configured fields to SetFieldable compatible instances
    $event_dispatch->listen(
      'framework.filter.created:\\Montage\\Field\\SetFieldable',
      function(\Montage\Event\FilterEvent $event){

        $instance = $event->getParam();
        $container = $event->getField('container');
        $config = $container->getConfig();
        
        if($config_map = $config->getField('class_fields')){
          
          $instance_class_name = get_class($instance);
          foreach(array($instance_class_name,'\\'.$instance_class_name) as $class_name){
          
            if(isset($config_map[$class_name])){
            
              ///\out::e('adding fields to '.$class_name);
              $instance->addFields($config_map[$class_name]);
            
            }//if
          
          }//foreach
          
        }//if
        
      }//closure
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
      'framework.filter.create:\\Montage\\Session',
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
      'framework.filter.create:\\Montage\\Request\\Requestable',
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
      'framework.filter.create:\\Montage\\Url',
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
    
    $event_dispatch->listen(
      'framework.filter.create:\\Screen',
      function(\Montage\Event\FilterEvent $event){

        $params = $event->getParam();
        $container = $event->getField('container');
        $request = $container->getRequest();
        
        if(!isset($params['is_quiet']) && !isset($params[0])){
        
          $params['is_quiet'] = $request->getField('quiet',false);
        
        }//if
        
        if(!isset($params['is_trace']) && !isset($params[1])){
        
          $params['is_trace'] = $request->getField('trace',false);
        
        }//if
        
        $event->setParam($params);
        
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
    
    error_reporting($this->config->getErrorLevel());
    ///error_reporting(E_ALL ^ E_USER_NOTICE);
    
    ini_set('display_errors',$this->config->showErrors() ? 'on' : 'off');
    
    // start/register the error handler if it hasn't been started...
    $container->getErrorHandler();
  
  }//method

}//class
