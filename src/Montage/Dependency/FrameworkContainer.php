<?php
/**
 *  The Framework dependency injection container   
 *  
 *  @version 0.1
 *  @author Jay Marcyes
 *  @since 10-7-11
 *  @package montage
 *  @subpackage Dependency 
 ******************************************************************************/
namespace Montage\Dependency;

use Montage\Event\Eventable;
use Montage\Event\FilterEvent;

class FrameworkContainer extends ReflectionContainer implements Eventable {

  /**
   *  get the Error handler
   *
   *  @since  10-28-11
   *  @return \Profile
   */
  public function getProfile($params = array()){
  
    return $this->findInstance('profile','\Profile',$params);
  
  }//method

  /**
   *  get the Error handler
   *
   *  @since  10-28-11
   *  @return Montage\Error
   */
  public function getErrorHandler($params = array()){
  
    return $this->findInstance('error_handler','\Montage\Error',$params);
  
  }//method

  /**
   *  get the Session 
   *
   *  @since  10-18-11
   *  @return Montage\Session
   */
  public function getSession($params = array()){
  
    return $this->findInstance('session','\Montage\Session',$params);
  
  }//method

  /**
   *  get the framework
   *  
   *  framework will almost always exist, the only time it won't is if you are
   *  creating this class outside of the Montage environment, in which case, you're
   *  on your own            
   *
   *  @since  10-18-11
   *  @return Montage\Framework      
   */
  public function getFramework($params = array()){
  
    return $this->findInstance('framework','\Montage\Framework',$params);
  
  }//method

  /**
   *  return the framework config
   *    
   *  @return Montage\Url
   */
  public function getUrl($params = array()){
  
    return $this->findInstance('url','\Montage\Url',$params);
  
  }//method

  /**
   *  return the framework config
   *  
   *  @since  9-26-11      
   *  @return Montage\Config\FrameworkConfig
   */
  public function getConfig($params = array()){
  
    return $this->findInstance('config','\Montage\Config\FrameworkConfig',$params);
  
  }//method
  
  /**
   *  get the event dispatcher
   *
   *  @Param  Dispatch  $dispatch   
   */
  public function setEventDispatch(\Montage\Event\Dispatch $dispatch){
  
    $this->setInstance('event_dispatch',$dispatch);
  
  }//method
  
  /**
   *  get the event dispatcher
   *  
   *  @since  8-25-11
   *  @return \Montage\Event\Dispatch
   */
  public function getEventDispatch($params = array()){
  
    return $this->findInstance('event_dispatch','\Montage\Event\Dispatch',$params);
  
  }//method
  
  /**
   *  broadcast the $event
   *
   *  honestly, I put this in the interface so there would be an easy method to check if
   *  the event dispatcher was actually set and broadcast the message, the only reason this
   *  is public is because you have to make interface methods public, otherwise I would make
   *  this protected         
   *      
   *  @param  Event $event   
   */
  public function broadcastEvent(\Montage\Event\Event $event){
  
    $event_dispatch = $this->getEventDispatch();
    return $event_dispatch->broadcast($event);
  
  }//method
  
  /**
   *  get the request instance
   *  
   *  @since  6-29-11
   *  @return Montage\Request\Requestable
   */
  public function getRequest($params = array()){
  
    return $this->findInstance('request','\Montage\Request\Requestable',$params);
  
  }//method
  
  /**
   *  get the response instance
   *  
   *  @since  6-29-11
   *  @return Montage\Response\Response
   */
  public function getResponse($params = array()){
  
    return $this->findInstance('response','\Montage\Response\Response',$params);
  
  }//method
  
  /**
   *  create the controller selector
   *  
   *  @return Montage\Controller\Select
   */
  public function getControllerSelect($params = array()){
  
    return $this->findInstance('controller_select','\Montage\Controller\Select',$params);
  
  }//method
  
  /**
   *  get the template object that corresponds to the template file found in $response
   *
   *  @since  7-7-11
   *  @return Montage\Response\Template         
   */
  public function getTemplate($params = array()){
    
    return $this->findInstance('template','\Montage\Response\Template',$params);
    
  }//method
  
  /**
   *  find the instance at $name, otherwise create the instance from $class_name
   *
   *  @since  10-8-11
   *  @param  string  $name the name/key/nickname of the instance
   *  @param  string  $class_name the full namespaced class name
   *  @param  array $params the parameters to use to create $class_name if it doesn't exist
   *  @return object      
   */
  protected function findInstance($name,$class_name,$params = array()){
  
    // canary...
    if(isset($this->instance_map[$name])){ return $this->instance_map[$name]; }//if
    
    $this->instance_map[$name] = $this->getInstance($class_name,$params);
    
    return $this->instance_map[$name];
  
  }//method
  
  /**
   *  handle actually running the onCreate callback
   *  
   *  @since  8-25-11
   *  @param  string  $class_name
   *  @param  array $params            
   *  @return array the same $params filtered through the callback
   */
  protected function handleOnCreate($class_name,array $params){
  
    $reflection = $this->getReflection();
    $cb_class_list = $this->getDependencies($class_name);
    if(!in_array($class_name,$cb_class_list)){ $cb_class_list[] = $class_name; }//if
    
    foreach($cb_class_list as $cb_class_name){
    
      $cb_class_key = $this->getEventKey('create',$cb_class_name);
      $event = new FilterEvent($cb_class_key,$params,array('container' => $this));
      $event = $this->broadcastEvent($event);
      $params = $event->getParam();
    
    }//foreach
  
    return $params;
  
  }//method
  
  /**
   *  handle actually running the onCreated callback
   *  
   *  @since  8-25-11
   *  @param  string  $class_name
   *  @param  object  $instance the newly created instance   
   */
  protected function handleOnCreated($class_name,$instance){
    
    $reflection = $this->getReflection();
    $cb_class_list = $this->getDependencies($class_name);
    if(!in_array($class_name,$cb_class_list)){ $cb_class_list[] = $class_name; }//if
    
    foreach($cb_class_list as $cb_class_name){
    
      $cb_class_key = $this->getEventKey('created',$cb_class_name);
      $event = new FilterEvent($cb_class_key,$instance,array('container' => $this));
      $event = $this->broadcastEvent($event);
      $instance = $event->getParam();
    
    }//foreach
    
    return $instance;
    
  }//method
  
  /**
   *  get the event key
   *  
   *  @param  string  $prefix
   *  @param  string  $class_name         
   *  @return string
   */
  protected function getEventKey($prefix,$class_name){
  
    // prepend the absolute namespace...
    if($class_name[0] !== '\\'){ $class_name = '\\'.$class_name; }//if
  
    return sprintf('framework.filter.%s:%s',$prefix,$class_name);
  
  }//method
  
  /**
   *  not used for this version of the container
   *  
   *  @see  parent::onCreate()
   */
  public function onCreate($class_name,$callback){
  
    throw new \BadMethodCallException(
      sprintf('Please Subscribe to the event "%s"',$this->getEventKey('create',$class_name))
    );
  
  }//method
  
  /**
   *  not used for this version of the container
   *  
   *  @see  parent::onCreated()
   */
  public function onCreated($class_name,$callback){
  
    throw new \BadMethodCallException(
      sprintf('Please Subscribe to the event "%s"',$this->getEventKey('created',$class_name))
    );

  }//method

}//class
