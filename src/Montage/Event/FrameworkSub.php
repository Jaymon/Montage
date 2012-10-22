<?php
/**
 *  By defualt, the framework monitors some events, this subscribes to those events
 *  for the framework
 *  
 *  this class should only be extended if you want to change core framework configuration
 *  and shouldn't be extended from stuff like Plugin, or app, subscription classes (those should
 *  all extend Montage\Event\Subscribe like this class does)
 *
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 10-2-12
 *  @package montage
 *  @subpackage event
 ******************************************************************************/
Namespace Montage\Event;

use Montage\Event\Event;
use Montage\Event\Subscribe as MontageSub;

class FrameworkSub extends MontageSub {

  /**
   * the framework configuration object
   *
   * @var \Montage\Config\FrameworkConfig
   */
  public $config = null;

  public function getEventName(){
    return array(
      'framework.filter.created:\\Montage\\Response\\Response' => array($this, 'handleCreatedResponse'),
      'framework.filter.created:\\Montage\\Response\\Template' => array($this, 'handleCreatedTemplate'),
      'framework.filter.created:\\Montage\\Cache\\Cacheable' => array($this, 'handleCreatedCacheable'),
      'framework.filter.created:\\Montage\\Field\\SetFieldable' => array($this, 'handleCreatedFieldable'),
      'framework.filter.create:\\Montage\\Session' => array($this, 'handleCreateSession'),
      'framework.filter.create:\\Montage\\Request\\Requestable' => array($this, 'handleCreateRequest'),
      'framework.filter.create:\\Montage\\Url' => array($this, 'handleCreateUrl'),
      'framework.filter.create:\\Screen' => array($this, 'handleCreateScreen'),
      'framework.preHandle' => array($this, 'handleStart')
    );
  }//method
  
  /**
   * handle the framework initialization
   */
  public function handleStart(\Montage\Event\Event $event){

    $this->handleError();
    mb_internal_encoding($this->config->getCharset());
    date_default_timezone_set($this->config->getTimezone());
  
  }//method

  public function handleCreatedResponse(\Montage\Event\FilterEvent $event){

    $instance = $event->getParam();
    
    // only set these values if nothing has been set
    if(!$instance->hasTemplate()){ $instance->setTemplate('page'); }//if
    if(!$instance->hasField('footer_template')){
      $instance->setField('footer_template','footer');
    }//if
    
  }//closure

  public function handleCreatedTemplate(\Montage\Event\FilterEvent $event){

    $instance = $event->getParam();
    $container = $event->getField('container');

    $config = $container->getConfig();
    $instance->addPaths($config->getField('view_paths'));
    
  }//method
  
  public function handleCreatedCacheable(\Montage\Event\FilterEvent $event){

    $instance = $event->getParam();
    if($cache = $instance->getCache()){
    
      $instance->importCache();
      
    }//if
    
  }//method

  /**
   * this will try and populate any fieldable instance with any set key/val pairs
   * from configuration
   */
  public function handleCreatedFieldable(\Montage\Event\FilterEvent $event){

    $instance = $event->getParam();
    $container = $event->getField('container');
    $config = $container->getConfig();
    
    if($config_map = $config->getField('class_fields')){
      
      $instance_class_name = get_class($instance);
      foreach(array($instance_class_name,'\\'.$instance_class_name) as $class_name){
      
        if(isset($config_map[$class_name])){
        
          $instance->addFields($config_map[$class_name]);
        
        }//if
      
      }//foreach
      
    }//if
    
  }//method

  public function handleCreateSession(\Montage\Event\FilterEvent $event){

    $params = $event->getParam();
  
    if(!isset($params['storage']) && !isset($params[0])){
    
      // there are about 6 children of the SessionInterface, so we are choosing here which one
      // we want....
      $params['storage'] = new \Symfony\Component\HttpFoundation\SessionStorage\NativeSessionStorage();
    
    }//if
  
    $event->setParam($params);
    
  }//method

  public function handleCreateRequest(\Montage\Event\FilterEvent $event){
  
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
    
  }//method

  public function handleCreateUrl(\Montage\Event\FilterEvent $event){
  
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
    
  }//method

  public function handleCreateScreen(\Montage\Event\FilterEvent $event){

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
    
  }//method

  /**
   *  handle setting up all the error stuff
   *
   *  @since  10-28-11   
   */
  protected function handleError(){
    
    $container = $this->getContainer();
    $config = $container->getConfig();
    
    error_reporting($config->getErrorLevel());
    ///error_reporting(E_ALL ^ E_USER_NOTICE);
    
    ini_set('display_errors',$config->showErrors() ? 'on' : 'off');
    
    // start/register the error handler if it hasn't been started...
    $container->getErrorHandler();
  
  }//method

}//class
