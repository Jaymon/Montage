<?php

/**
 *  this class actually handles the request
 *  
 *  while you can extend this class, don't do it unless you know what you are doing
 *  and absolutely need to as you can screw up your entire app in all kinds of ways      
 *   
 *  @version 0.1
 *  @author Jay Marcyes {@link http://marcyes.com}
 *  @since 5-26-10
 *  @package montage 
 ******************************************************************************/
class montage_handle extends montage_base {

  /**
   *  switched to true when the class is instantiated
   *  @var  boolean
   */
  protected static $is_started = false;
  
  /**
   *  how many times the handle() method can be called before the class throws an
   *  exception, this is here to avoid infinite recursion
   *  
   *  is there a reason to go more than 15?         
   *
   *  @var  integer   
   */
  protected $handle_max_count = 15;

  final function __construct(){
  
    // canary...
    if(self::$is_started){
      throw new RuntimeException(
        sprintf(
          '%s has alread started, no point in starting it again',
          get_class($this)
        )
      );
    }//if
  
    self::$is_started = true;
  
    $debug = montage::getSettings()->getDebug();
    if($debug){ montage_profile::start(__METHOD__); }//if
    
    // needed global classes...
    $event = montage::getEvent();
    $use_template = false;
    
    // let's do this... 
    try{
    
      // let any child class do its init stuff...
      if($debug){ montage_profile::start('start'); }//if
      $this->start();
      if($debug){ montage_profile::stop(); }//if
      
      // get all the start classes and run them...
      if($debug){ montage_profile::start('start classes start'); }//if
      $start_list = $this->startClasses(montage_core::getStartClassNames());
      if($debug){ montage_profile::stop(); }//if
      
      // get all the filters and start them...
      if($debug){ montage_profile::start('filter classes start'); }//if
      $filter_list = $this->startClasses(montage_core::getFilterClassNames());
      if($debug){ montage_profile::stop(); }//if
      
      $use_template = $this->handle();
      
      // stop all the filters...
      if($debug){ montage_profile::start('filter classes stop'); }//if
      $this->stopClasses($filter_list);
      if($debug){ montage_profile::stop(); }//if
      
      // stop all the start classes...
      if($debug){ montage_profile::start('start classes stop'); }//if
      $this->stopClasses($start_list);
      if($debug){ montage_profile::stop(); }//if
      
    }catch(Exception $e){
    
      $use_template = $this->handle();
    
    }//try/catch
    
    // needed global classes...
    $request = montage::getRequest();
    $response = montage::getResponse();
    
    // chances are stuff is going to be echo'ed to the user in this method...
    if($debug){ montage_profile::start('response'); }//if
    $response->handle($use_template);
    if($debug){ montage_profile::stop(); }//if
    
    
    // profile, method...
    if($debug){ montage_profile::stop(); }//if
    
  }//method

  /**
   *  if this class is extended, you can override this method to do custom init stuff
   *  for your child class        
   */
  protected function start(){}//method
  
  /**
   *  handle a request, warts and all
   *  
   *  the reason this is separate from {@link handle()} so that it can call it again
   *  to try and handle (in case of error or the like)
   *  
   *  @param  array $filter_list  a list of string names of classes that extend montage_filter
   *  @return boolean $use_template to pass into the response handler
   */
  protected function handle(){
  
    // canary, avoid infinite internal redirects...
    $this->handleRecursion();
  
    $debug = montage::getSettings()->getDebug();
    
    // profile...
    if($debug){ montage_profile::start(__METHOD__); }//if

    $use_template = false;
    $request = montage::getRequest();
    
    try{
      
      // profile...
      if($debug){ montage_profile::start('controller'); }//if
      $use_template = $request->handle();
      if($debug){ montage_profile::stop(); }//if
    
    }catch(Exception $e){
    
      $use_template = $this->handleException($e);
    
    }//try/catch
    
    // profile...
    if($debug){ montage_profile::stop(); }//if
  
    return $use_template;
  
  }//method
  
  /**
   *  handle a thrown exception
   *
   *  @return boolean "$use_template
   */
  protected function handleException(Exception $e){
  
    $use_template = false;
    
    // needed global classes...
    $request = montage::getRequest();
    $event = montage::getEvent();
    
    if($e instanceof montage_forward_exception){
    
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'forwarding to controller %s::%s via forward exception at %s:%s',
            $request->getControllerClass(),
            $request->getControllerMethod(),
            $e->getFile(),
            $e->getLine()
          )
        )
      );
    
      // we forwarded to another controller so we're going another round...
      $use_template = $this->handle();
    
    }else if($e instanceof montage_redirect_exception){
    
      // we don't really need to do anything since the redirect header should have been called
      $use_template = false;
      $response->set('');
      
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'redirect to %s',
            $e->getMessage()
          )
        )
      );
    
    }else if($e instanceof montage_stop_exception){
      
      $use_template = false; // since a stop signal was caught we'll want to use $response->get()
      
      // do nothing, we've stopped execution so we'll go ahead and let the response take over
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'execution stopped via stop exception at %s:%s',
            $e->getFile(),
            $e->getLine()
          )
        )
      );
      
    }else{
      
      $request->setErrorHandler($e);
      
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => 
          sprintf(
            'forwarding to controller %s::%s to handle exception thrown at %s:%s',
            $request->getControllerClass(),
            $request->getControllerMethod(),
            $e->getFile(),
            $e->getLine()
          )
        )
      );
      
      // send it back through for another round...
      $use_template = $this->handle();
      
    }//try/catch
  
    return $use_template;
  
  }//method
  
  /**
   *  check for infinite recursion, throw an exception if found
   *  
   *  this is done by keeping an internal count of how many times the {@link handle()}
   *  method has been called, if that count reaches the max count then an exception is
   *  thrown
   *  
   *  @return integer the current count
   */
  protected function handleRecursion(){
  
    $ir_field = 'montage_handle::infinite_recursion_count'; 
    $ir_count = $this->getField($ir_field,0);
    if($ir_count > $this->handle_max_count){
      throw new RuntimeException(
        sprintf(
          'The application has internally redirected more than %s times, something seems to '
          .'be wrong and the app is bailing to avoid infinite recursion!',
          $this->handle_max_count
        )
      );
    }else{
      $ir_count = $this->bumpField($ir_field,1);
    }//if/else
    
    return $ir_count;
  
  }//method
  
  /**
   *  given a list of class names, go ahead and instantiate them
   *  
   *  @since  5-31-10   
   *  @param  array $class_name_list  a list of class names to be instantiated
   *  @return array a list of class instances
   */
  protected function startClasses($class_name_list){
  
    // canary...
    if(empty($class_name_list)){ return array(); }//if

    $event = montage::getEvent();
    $ret_instance_list = array();

    foreach($class_name_list as $key => $class_name){
    
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => sprintf('starting class %s',$class_name))
      );
    
      $ret_instance_list[$key] = montage_factory::getInstance($class_name);
      
    }//foreach
    
    return $ret_instance_list;
  
  }//method
  
  /**
   *  given a list of class instances, run each instance's stop() method
   *  
   *  @since  5-31-10   
   *  @param  array $instance_list  a list of class instances
   *  @return boolean true if stop ran on all instances
   */
  protected function stopClasses($instance_list){
  
    // canary...
    if(empty($instance_list)){ return false; }//if
    
    $event = montage::getEvent();
    
    // run all the filters again to stop them...
    foreach($instance_list as $instance){
    
      $event->broadcast(
        montage_event::KEY_INFO,
        array('msg' => sprintf('stopping %s',get_class($instance)))
      );
      
      $instance->stop();
      
    }//foreach
    
    return true;
    
  }//method

}//class     
