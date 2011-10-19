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

class FrameworkContainer extends ReflectionContainer {

  /**
   *  get the Session 
   *
   *  @since  10-18-11
   *  @return Montage\Session
   */
  public function getSession($params = array()){
  
    return $this->findInstance('session','Montage\Session',$params);
  
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
  
    return $this->findInstance('framework','Montage\Framework',$params);
  
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
  
    return $this->findInstance('config','\Montage\Config\frameworkConfig',$params);
  
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
   *  get the request instance
   *  
   *  @since  6-29-11
   *  @return Montage\Request\Requestable
   */
  public function getRequest($params = array()){
  
    return $this->findInstance('request','Montage\Request\Requestable',$params);
  
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
  
    return $this->findInstance('controller_select','Montage\Controller\Select',$params);
  
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

}//class
